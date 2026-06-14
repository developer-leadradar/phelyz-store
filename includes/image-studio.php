<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * Phelyz Image Studio — helpers & AI provider abstraction.
 *
 * The pluggable provider layer means swapping Gemini for OpenAI / Replicate /
 * Stability later is a single class swap, not a rewrite of any UI.
 */

// ── Settings ────────────────────────────────────────────────────────────────

function imageStudioSettings() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $file = __DIR__ . '/../data/settings.json';
    $cache = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    return $cache;
}

function imageStudioApiKey() {
    // Prefer env var (set in Vercel) over settings.json (set via admin UI)
    $envKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
    if (!empty($envKey)) return $envKey;
    $s = imageStudioSettings();
    return $s['gemini_api_key'] ?? '';
}

function imageStudioProvider() {
    $s = imageStudioSettings();
    return $s['image_studio_provider'] ?? 'gemini';
}

// ── AI Provider Interface ───────────────────────────────────────────────────

interface ImageGenProvider {
    /** @return array{ok:bool, image_path?:string, message?:string} */
    public function generateModelShot(string $productImagePath, array $preset, string $category): array;
    public function isConfigured(): bool;
    public function providerName(): string;
}

/**
 * Gemini 2.5 Flash Image (the model behind "Nano Banana").
 * Endpoint: https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent
 */
class GeminiImageProvider implements ImageGenProvider {
    private string $apiKey;

    public function __construct(string $apiKey = '') {
        $this->apiKey = $apiKey ?: imageStudioApiKey();
    }

    public function providerName(): string { return 'Gemini 2.5 Flash Image'; }

    public function isConfigured(): bool { return !empty($this->apiKey); }

    public function generateModelShot(string $productImagePath, array $preset, string $category): array {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'GEMINI_API_KEY is not configured. Add it under Settings → Image Studio.'];
        }

        // Read the product image and base64-encode (Gemini expects inline data)
        $imageBytes = @file_get_contents($productImagePath);
        if ($imageBytes === false) {
            return ['ok' => false, 'message' => 'Could not read input image.'];
        }
        $mime = imageStudioGuessMime($productImagePath);

        $prompt = buildModelShotPrompt($preset, $category);

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => [
                        'mime_type' => $mime,
                        'data'      => base64_encode($imageBytes),
                    ]],
                ],
            ]],
            // Image-out modality
            'generationConfig' => [
                'responseModalities' => ['IMAGE'],
            ],
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent?key=' . urlencode($this->apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 90,
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'message' => 'Network error calling Gemini: ' . $err];
        }
        if ($code >= 400) {
            return ['ok' => false, 'message' => 'Gemini API error (' . $code . '): ' . substr($response, 0, 500)];
        }

        $body = json_decode($response, true);
        $imageB64 = null;
        foreach ($body['candidates'][0]['content']['parts'] ?? [] as $part) {
            if (isset($part['inline_data']['data'])) { $imageB64 = $part['inline_data']['data']; break; }
            if (isset($part['inlineData']['data']))  { $imageB64 = $part['inlineData']['data'];  break; }
        }
        if (!$imageB64) {
            return ['ok' => false, 'message' => 'Gemini did not return an image. Try a different photo or simplify the prompt.'];
        }

        // Save out
        $bytes = base64_decode($imageB64);
        $filename = 'products/model-' . uniqid() . '.png';
        $saved = imageStudioSaveBinary($bytes, $filename, 'image/png');
        if (!$saved) {
            return ['ok' => false, 'message' => 'Failed to save generated image.'];
        }
        return ['ok' => true, 'image_path' => $saved];
    }
}

// ── Provider factory ────────────────────────────────────────────────────────

function imageStudioGetProvider(): ImageGenProvider {
    $name = imageStudioProvider();
    switch (strtolower($name)) {
        case 'gemini':
        default:
            return new GeminiImageProvider();
    }
}

// ── Prompt builder ──────────────────────────────────────────────────────────

/**
 * Build the model-shot prompt from a preset + jewellery category.
 * The prompt mirrors the user's original Nano-Banana brief, with the preset's
 * details injected so each shoot can match the brand's chosen "look".
 */
function buildModelShotPrompt(array $preset, string $category): string {
    $skin    = trim($preset['skin_tone']      ?? 'medium brown');
    $gender  = trim($preset['gender']         ?? 'female');
    $age     = trim($preset['age_range']      ?? '25-35');
    $pose    = trim($preset['pose_style']     ?? 'elegant');
    $light   = trim($preset['lighting_mood']  ?? 'soft studio');
    $extra   = trim($preset['extra_prompt']   ?? '');

    $mountMap = [
        'rings'        => 'gracefully on the hand, close-up on the ring finger, fingers slightly curled',
        'necklaces'    => 'on the collarbone and neckline, head tilted to catch the chain',
        'bracelets'    => 'on the wrist, hand resting elegantly',
        'earrings'     => 'on the earlobe, three-quarter face profile',
        'pendants'     => 'on the chest below the collarbone, falling naturally',
        'watches'      => 'on the wrist, hand turned to show the dial',
        'bridal sets'  => 'on the hand with engagement-style framing',
        'mens jewelry' => 'on the appropriate body part with masculine framing',
    ];
    $catKey = strtolower(trim($category));
    $mount  = $mountMap[$catKey] ?? 'naturally on the body in the most flattering placement';

    return <<<PROMPT
You are an expert product-photography AI. Generate ONE ultra-realistic, ultra-sharp commercial product photograph (8k style).

ABSOLUTE PRODUCT INTEGRITY — DO NOT alter, redesign, or hallucinate features of the jewellery shown in the reference image. Preserve every diamond, stone, prong, link, and metallic finish exactly. Only adjust positioning, angle, contact shadows, and lighting so it sits naturally on the model.

Subject: a professional {$skin}-skinned {$gender} fashion model, age {$age}, with hyper-realistic skin (subtle pores, natural texture, no AI-smoothing). Pose: {$pose}.

Placement: the jewellery is worn {$mount}. The piece must interact with the skin with realistic micro-shadows and reflections.

Setting: clean high-end studio backdrop, soft-focus luxury feel, {$light} lighting. Commercial product photography sharpness — the jewellery must be tack-sharp with zero blur.

{$extra}

Output ONLY the image. No text overlays, no watermarks, no extra ornaments on the jewellery.
PROMPT;
}

// ── File helpers ────────────────────────────────────────────────────────────

function imageStudioGuessMime(string $path): string {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return [
        'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ][$ext] ?? 'application/octet-stream';
}

/**
 * Save raw image bytes either to Supabase Storage (production) or to the
 * local /uploads folder. Returns a publicly-resolvable path on success.
 */
function imageStudioSaveBinary(string $bytes, string $relativeName, string $mime = 'image/png') {
    // Supabase Storage path (matches the existing uploadImage() helper)
    if (defined('SUPABASE_URL') && !empty(SUPABASE_URL) &&
        defined('SUPABASE_SERVICE_KEY') && !empty(SUPABASE_SERVICE_KEY) &&
        defined('SUPABASE_BUCKET') && !empty(SUPABASE_BUCKET)) {
        $url = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $relativeName;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
                'apikey: ' . SUPABASE_SERVICE_KEY,
                'Content-Type: ' . $mime,
                'x-upsert: true',
            ],
            CURLOPT_POSTFIELDS     => $bytes,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 || $code === 201) {
            return rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $relativeName;
        }
        error_log('Image Studio Supabase save failed: ' . substr((string)$resp, 0, 500));
        return false;
    }

    // Local /uploads fallback
    $uploadDir = (defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../uploads/');
    $fullPath  = $uploadDir . $relativeName;
    $dir       = dirname($fullPath);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (file_put_contents($fullPath, $bytes) !== false) {
        return 'uploads/' . $relativeName;
    }
    return false;
}

// ── Template + preset helpers ───────────────────────────────────────────────

function getImageTemplates() {
    try { return getDB()->fetchAll("SELECT * FROM image_templates ORDER BY is_default DESC, name ASC"); }
    catch (Exception $e) { return []; }
}

function getDefaultImageTemplate($categoryId = null) {
    $db = getDB();
    try {
        if ($categoryId) {
            $row = $db->fetchOne(
                "SELECT * FROM image_templates WHERE category_id = ? ORDER BY is_default DESC, id ASC LIMIT 1",
                [(int)$categoryId]
            );
            if ($row) return $row;
        }
        return $db->fetchOne("SELECT * FROM image_templates WHERE is_default = 1 ORDER BY id ASC LIMIT 1");
    } catch (Exception $e) { return null; }
}

function getModelPresets() {
    try { return getDB()->fetchAll("SELECT * FROM model_presets ORDER BY is_default DESC, name ASC"); }
    catch (Exception $e) { return []; }
}

function getDefaultModelPreset() {
    try {
        $row = getDB()->fetchOne("SELECT * FROM model_presets WHERE is_default = 1 ORDER BY id ASC LIMIT 1");
        return $row ?: getDB()->fetchOne("SELECT * FROM model_presets ORDER BY id ASC LIMIT 1");
    } catch (Exception $e) { return null; }
}

// ── Free-tier usage tracker ─────────────────────────────────────────────────

function imageStudioGenCountToday() {
    $today = date('Y-m-d');
    try {
        $row = getDB()->fetchOne(
            "SELECT COUNT(*) AS c FROM image_studio_outputs
             WHERE output_type = 'model' AND DATE(created_at) = ?",
            [$today]
        );
        return (int)($row['c'] ?? 0);
    } catch (Exception $e) { return 0; }
}

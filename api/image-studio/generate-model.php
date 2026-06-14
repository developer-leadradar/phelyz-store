<?php
/**
 * Calls the configured AI provider to generate a "model wearing the jewellery"
 * shot from a product image already saved to storage.
 *
 * Input JSON: { source_path, preset_id?, category?, job_id? }
 * Output JSON: { ok, output_id, output_path, message? }
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/image-studio.php';

header('Content-Type: application/json');
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$sourcePath = trim($data['source_path'] ?? '');
$presetId   = isset($data['preset_id']) ? (int)$data['preset_id'] : 0;
$category   = sanitize($data['category'] ?? '');
$jobId      = isset($data['job_id']) ? (int)$data['job_id'] : 0;

if (empty($sourcePath)) {
    echo json_encode(['ok' => false, 'message' => 'source_path is required.']);
    exit;
}

// Resolve the source path to a local file we can read.
// Accepts: full URLs (Supabase), relative paths like "uploads/products/x.png"
$localPath = null;
if (preg_match('#^https?://#i', $sourcePath)) {
    // Download to a temp file so the provider can read bytes
    $tmp = tempnam(sys_get_temp_dir(), 'studio_src_');
    $bytes = @file_get_contents($sourcePath);
    if ($bytes !== false && file_put_contents($tmp, $bytes) !== false) {
        $localPath = $tmp;
    }
} else {
    $localPath = __DIR__ . '/../../' . ltrim($sourcePath, '/');
    if (!file_exists($localPath)) $localPath = null;
}

if (!$localPath) {
    echo json_encode(['ok' => false, 'message' => 'Could not read source image at ' . $sourcePath]);
    exit;
}

// Resolve preset
$preset = null;
if ($presetId > 0) {
    foreach (getModelPresets() as $p) if ((int)$p['id'] === $presetId) { $preset = $p; break; }
}
if (!$preset) $preset = getDefaultModelPreset();
if (!$preset) {
    $preset = [
        'name' => 'fallback', 'skin_tone' => 'deep brown', 'gender' => 'female',
        'age_range' => '25-32', 'pose_style' => 'elegant',
        'lighting_mood' => 'soft studio', 'extra_prompt' => '',
    ];
}

// Call provider
$provider = imageStudioGetProvider();
if (!$provider->isConfigured()) {
    echo json_encode([
        'ok' => false,
        'message' => 'AI provider not configured. Add your Gemini API key under Settings → Image Studio.',
        'needs_config' => true,
    ]);
    exit;
}

$result = $provider->generateModelShot($localPath, $preset, $category);

// Clean up temp file if we created one
if (isset($tmp) && file_exists($tmp)) @unlink($tmp);

if (!$result['ok']) {
    echo json_encode(['ok' => false, 'message' => $result['message'] ?? 'Generation failed.']);
    exit;
}

// Persist output row
$db = getDB();
try {
    if (!$jobId) {
        $jobId = $db->insert('image_studio_jobs', [
            'admin_user_id'   => $_SESSION['user_id'] ?? null,
            'model_preset_id' => (int)($preset['id'] ?? 0) ?: null,
            'status'          => 'processing',
            'total_inputs'    => 1,
        ]);
    }
    $outputId = $db->insert('image_studio_outputs', [
        'job_id'         => $jobId,
        'input_filename' => basename($sourcePath),
        'output_type'    => 'model',
        'output_path'    => $result['image_path'],
        'status'         => 'pending',
    ]);
} catch (Exception $e) {
    echo json_encode([
        'ok' => true,
        'output_path' => $result['image_path'],
        'warning' => 'Generated but not tracked in DB (run migrations).',
    ]);
    exit;
}

echo json_encode([
    'ok'          => true,
    'output_id'   => $outputId,
    'job_id'      => $jobId,
    'output_path' => $result['image_path'],
]);

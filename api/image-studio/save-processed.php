<?php
/**
 * Saves a browser-processed image (data URL) to storage.
 * Called by the Image Studio after the canvas pipeline finishes per-image.
 *
 * Input JSON: { job_id?, input_filename, image_data_url, output_type ('template'|'model') }
 * Output JSON: { ok, output_id, output_path }
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/image-studio.php';

header('Content-Type: application/json');

// Admin-only
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$dataUrl       = $data['image_data_url'] ?? '';
$inputFilename = sanitize($data['input_filename'] ?? 'image');
$outputType    = in_array($data['output_type'] ?? 'template', ['template', 'model'], true)
                 ? $data['output_type'] : 'template';
$jobId         = isset($data['job_id']) ? (int)$data['job_id'] : 0;

if (!preg_match('#^data:(image/[a-z0-9.+-]+);base64,(.*)$#i', $dataUrl, $m)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid image data URL.']);
    exit;
}
$mime  = $m[1];
$bytes = base64_decode($m[2]);
if ($bytes === false || strlen($bytes) === 0) {
    echo json_encode(['ok' => false, 'message' => 'Empty image payload.']);
    exit;
}

$ext = ['image/webp' => 'webp', 'image/png' => 'png', 'image/jpeg' => 'jpg'][$mime] ?? 'png';
$relativeName = 'products/studio-' . $outputType . '-' . uniqid() . '.' . $ext;

$publicPath = imageStudioSaveBinary($bytes, $relativeName, $mime);
if (!$publicPath) {
    echo json_encode(['ok' => false, 'message' => 'Could not save the processed image.']);
    exit;
}

// Create/attach to a job row if requested
$db = getDB();
try {
    if (!$jobId) {
        $jobId = $db->insert('image_studio_jobs', [
            'admin_user_id' => $_SESSION['user_id'] ?? null,
            'status'        => 'processing',
            'total_inputs'  => 1,
        ]);
    }
    $outputId = $db->insert('image_studio_outputs', [
        'job_id'         => $jobId,
        'input_filename' => $inputFilename,
        'output_type'    => $outputType,
        'output_path'    => $publicPath,
        'status'         => 'pending',
    ]);
} catch (Exception $e) {
    // DB write failed but the image is saved; return the path anyway
    echo json_encode([
        'ok' => true,
        'output_path' => $publicPath,
        'warning' => 'Image saved to storage but not tracked in DB (run migrations/add_image_studio.sql).'
    ]);
    exit;
}

echo json_encode([
    'ok'          => true,
    'output_id'   => $outputId,
    'job_id'      => $jobId,
    'output_path' => $publicPath,
]);

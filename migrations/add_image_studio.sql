-- ============================================================
-- Migration: Image Studio (MySQL / XAMPP)
-- Reusable templates, model presets, batch jobs, generated outputs
-- ============================================================

-- 1. Reusable backdrop templates
CREATE TABLE IF NOT EXISTS image_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    description TEXT NULL,
    category_id INT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Reusable model personas for AI generation
CREATE TABLE IF NOT EXISTS model_presets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    skin_tone VARCHAR(60) NOT NULL DEFAULT 'medium brown',
    gender VARCHAR(20) NOT NULL DEFAULT 'female',
    age_range VARCHAR(20) NOT NULL DEFAULT '25-35',
    pose_style VARCHAR(60) NOT NULL DEFAULT 'elegant',
    lighting_mood VARCHAR(60) NOT NULL DEFAULT 'soft studio',
    extra_prompt TEXT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Batch jobs
CREATE TABLE IF NOT EXISTS image_studio_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NULL,
    template_id INT NULL,
    model_preset_id INT NULL,
    options TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    total_inputs INT NOT NULL DEFAULT 0,
    completed_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Generated outputs (one per processed image, per type: 'template' or 'model')
CREATE TABLE IF NOT EXISTS image_studio_outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    input_filename VARCHAR(255) NULL,
    output_type VARCHAR(30) NOT NULL DEFAULT 'template',
    output_path VARCHAR(500) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    assigned_product_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES image_studio_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_job (job_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Seed a sensible default model preset
INSERT INTO model_presets (name, skin_tone, gender, age_range, pose_style, lighting_mood, extra_prompt, is_default)
SELECT 'Amaka — Editorial', 'deep brown', 'female', '25-32', 'elegant editorial', 'soft natural studio', 'graceful hands and neckline, subtle smile, luxury jewellery model', 1
WHERE NOT EXISTS (SELECT 1 FROM model_presets WHERE is_default = 1);

SELECT 'Image Studio migration completed!' AS message;

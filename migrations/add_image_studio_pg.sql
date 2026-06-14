-- ============================================================
-- Migration: Image Studio (Supabase / PostgreSQL)
-- ============================================================

CREATE TABLE IF NOT EXISTS image_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    description TEXT NULL,
    category_id INT NULL REFERENCES categories(id) ON DELETE SET NULL,
    is_default SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_image_templates_category ON image_templates(category_id);
CREATE INDEX IF NOT EXISTS idx_image_templates_default  ON image_templates(is_default);

CREATE TABLE IF NOT EXISTS model_presets (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    skin_tone VARCHAR(60) NOT NULL DEFAULT 'medium brown',
    gender VARCHAR(20) NOT NULL DEFAULT 'female',
    age_range VARCHAR(20) NOT NULL DEFAULT '25-35',
    pose_style VARCHAR(60) NOT NULL DEFAULT 'elegant',
    lighting_mood VARCHAR(60) NOT NULL DEFAULT 'soft studio',
    extra_prompt TEXT NULL,
    is_default SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS image_studio_jobs (
    id SERIAL PRIMARY KEY,
    admin_user_id INT NULL,
    template_id INT NULL,
    model_preset_id INT NULL,
    options TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    total_inputs INT NOT NULL DEFAULT 0,
    completed_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    completed_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_image_studio_jobs_status  ON image_studio_jobs(status);
CREATE INDEX IF NOT EXISTS idx_image_studio_jobs_created ON image_studio_jobs(created_at);

CREATE TABLE IF NOT EXISTS image_studio_outputs (
    id SERIAL PRIMARY KEY,
    job_id INT NOT NULL REFERENCES image_studio_jobs(id) ON DELETE CASCADE,
    input_filename VARCHAR(255) NULL,
    output_type VARCHAR(30) NOT NULL DEFAULT 'template',
    output_path VARCHAR(500) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    assigned_product_id INT NULL REFERENCES products(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_image_studio_outputs_job    ON image_studio_outputs(job_id);
CREATE INDEX IF NOT EXISTS idx_image_studio_outputs_status ON image_studio_outputs(status);

-- Seed a default model preset (only if none exists)
INSERT INTO model_presets (name, skin_tone, gender, age_range, pose_style, lighting_mood, extra_prompt, is_default)
SELECT 'Amaka — Editorial', 'deep brown', 'female', '25-32', 'elegant editorial', 'soft natural studio',
       'graceful hands and neckline, subtle smile, luxury jewellery model', 1
WHERE NOT EXISTS (SELECT 1 FROM model_presets WHERE is_default = 1);

SELECT 'Image Studio migration completed!' AS message;

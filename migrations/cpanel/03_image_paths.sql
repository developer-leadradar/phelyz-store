-- ============================================================
-- Repoint image columns from Supabase Storage to local uploads/
-- Run AFTER 02_data.sql. Files live in uploads/products/.
-- ============================================================
UPDATE products SET image='uploads/products/6a441229761be.png' WHERE id=10;
UPDATE products SET image='uploads/products/6a359371e31f8.jpg' WHERE id=21;
UPDATE products SET image='uploads/products/6a3596b79bd94.jpg' WHERE id=23;
UPDATE products SET image='uploads/products/6a359bf8ec713.jpg' WHERE id=24;
UPDATE products SET image='uploads/products/6a35a4393c473.jpg' WHERE id=27;
UPDATE products SET image='uploads/products/6a35a6ee0ab8a.jpg' WHERE id=28;
UPDATE products SET image='uploads/products/6a35a904719e7.jpg' WHERE id=29;
UPDATE products SET image='uploads/products/6a35b07e2d97f.jpg' WHERE id=30;
UPDATE products SET image='uploads/products/6a35b41104751.jpg' WHERE id=31;
UPDATE products SET image='uploads/products/6a35b51c2629f.jpg' WHERE id=32;
UPDATE products SET image='uploads/products/6a44144dc3c2b.png' WHERE id=33;
UPDATE products SET image='uploads/products/6a60a33a09f1f.png' WHERE id=34;
UPDATE product_images SET image_path='uploads/products/6a35a439b6da3.jpg' WHERE id=4;
UPDATE product_images SET image_path='uploads/products/6a35a43a5b3fb.jpg' WHERE id=5;
UPDATE product_images SET image_path='uploads/products/6a35b07e9cc11.jpg' WHERE id=6;
UPDATE product_images SET image_path='uploads/products/6a35b411c7705.jpg' WHERE id=7;
UPDATE product_images SET image_path='uploads/products/6a35b51cd35cc.jpg' WHERE id=8;
SELECT 'Image paths repointed to local uploads.' AS message;
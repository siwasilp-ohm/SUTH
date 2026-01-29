# SUTH

## HICM Assessment Web Application
ระบบประเมินสถานประกอบการตามเกณฑ์ HICM V2025 ด้วย PHP + MySQL รองรับมือถือและแท็บเล็ต

### Quick Start (XAMPP)
1. สร้างฐานข้อมูลชื่อ `hicm` ใน phpMyAdmin
2. รันไฟล์ `database/schema.sql` เพื่อสร้างตาราง
3. นำเข้า indicator จากไฟล์ Excel:
   - รัน `python scripts/extract_indicators.py`
   - รัน `php scripts/import_indicators.php`
4. สร้างบัญชีตัวอย่าง:
   - รัน `php scripts/seed.php`
5. เปิด `http://localhost/SUTH/public` เพื่อใช้งาน
6. สามารถลงทะเบียนบริษัทใหม่ได้ที่หน้า Register

> ตั้งค่า DB ผ่าน environment variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.

## Documentation
- [HURS Data Entry Manual (REF.pdf transcription)](docs/hurs-data-entry-manual.md)
- [HICM V2025 Assessment Source Files](docs/hicm-assessment-files.md)

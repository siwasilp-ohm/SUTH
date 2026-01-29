<?php
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/data.php';

$data = load_hicm_data();
$pillars = $data['pillars'];

include __DIR__ . '/partials/header.php';
?>
<section class="hero">
    <div>
        <span class="badge">HICM V2025</span>
        <h1>ระบบประเมินสถานประกอบการตามเกณฑ์ HICM V2025</h1>
        <p class="lead">
            ศูนย์กลางการประเมินสุขภาวะองค์กรที่รวม 4 Pillars และ 60 ตัวชี้วัด พร้อมการคิดคะแนนแบบ Real-time
            และ dashboard ที่ดูภาพรวมได้ทันทีทั้งบริษัทและกรรมการ.
        </p>
        <div class="cta">
            <a class="btn primary" href="/login.php">เริ่มใช้งาน</a>
            <a class="btn ghost" href="#pillars">ดูโครงสร้าง Pillars</a>
        </div>
    </div>
    <div class="hero-card">
        <div class="pulse"></div>
        <h3>HICM Assessment Tool</h3>
        <p>รองรับมือถือและแท็บเล็ต พร้อม UI แบบโปรเฟสชันนัลและอนิเมชัน</p>
        <div class="stats">
            <div>
                <strong>4</strong>
                <span>Pillars</span>
            </div>
            <div>
                <strong>60</strong>
                <span>Indicators</span>
            </div>
            <div>
                <strong>1,000</strong>
                <span>Total Score</span>
            </div>
        </div>
    </div>
</section>

<section id="pillars" class="pillars">
    <h2>โครงสร้างการประเมิน</h2>
    <div class="pillar-grid">
        <?php foreach ($pillars as $pillar): ?>
            <div class="pillar-card">
                <h3><?= htmlspecialchars($pillar['code']) ?></h3>
                <p><?= nl2br(htmlspecialchars($pillar['name'])) ?></p>
                <div class="pillar-meta">ตัวชี้วัด <?= htmlspecialchars($pillar['indicator_count']) ?> ข้อ • น้ำหนัก <?= htmlspecialchars($pillar['weight']) ?> คะแนน</div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="workflow">
    <h2>Workflow การประเมิน</h2>
    <div class="workflow-steps">
        <div class="step">
            <span>1</span>
            <h4>บริษัททำแบบประเมิน</h4>
            <p>กรอกข้อมูล แนบหลักฐาน และดูคะแนนประเมินตนเองแบบเรียลไทม์</p>
        </div>
        <div class="step">
            <span>2</span>
            <h4>กรรมการตรวจสอบ</h4>
            <p>ตรวจทานข้อมูลทุกบริษัท ให้คะแนน และติดตามสถานะ</p>
        </div>
        <div class="step">
            <span>3</span>
            <h4>สรุปผลและรายงาน</h4>
            <p>Export รายงาน PDF/Excel และแสดงผลใน Dashboard</p>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>

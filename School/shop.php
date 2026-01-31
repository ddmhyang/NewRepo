<?php
// shop.php : 유저 전용 상점 (한글 타입 표시 적용)
require_once 'common.php';

if (!isset($_SESSION['uid'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.replace('index.php');</script>";
    exit;
}

$my_id = $_SESSION['uid'];

// [재고 리필 로직]
function check_shop_refill() {
    global $pdo; // global pdo 사용
    $shops = sql_fetch_all("SELECT * FROM School_Shop_Config WHERE refill_type != 'NONE'");
    foreach($shops as $s) {
        $do_refill = false;
        $now = time();
        $last = strtotime($s['last_refilled_at']);
        
        if ($s['refill_type'] === 'DAILY') {
            // 매일 특정 시간 리필
            $target_time = strtotime(date('Y-m-d') . ' ' . $s['refill_value']);
            if (date('Ymd', $last) != date('Ymd') && $now >= $target_time) $do_refill = true;
        } elseif ($s['refill_type'] === 'TIME') {
            // 일정 시간마다 리필 (분 단위)
            if ($now - $last >= intval($s['refill_value']) * 60) $do_refill = true;
        }
        
        if ($do_refill) {
            sql_exec("UPDATE School_Shop_Config SET stock = stock + ?, last_refilled_at = NOW() WHERE id=?", [$s['refill_amount'], $s['id']]);
        }
    }
}
check_shop_refill();

// [구매 처리]
if (isset($_POST['action']) && $_POST['action'] === 'buy') {
    $item_id = to_int($_POST['item_id']);
    $count = to_int($_POST['count']);
    if ($count <= 0) { echo "<script>alert('수량 오류'); history.back();</script>"; exit; }

    try {
        $pdo->beginTransaction();
        $me = sql_fetch("SELECT point, name FROM School_Members WHERE id = ? FOR UPDATE", [$my_id]);
        $shop_item = sql_fetch("SELECT i.name, i.price, s.stock FROM School_Shop_Config s JOIN School_Item_Info i ON s.item_id = i.item_id WHERE s.item_id = ?", [$item_id]);

        if (!$shop_item) throw new Exception("아이템이 존재하지 않습니다.");
        if ($shop_item['stock'] != -1 && $shop_item['stock'] < $count) throw new Exception("재고가 부족합니다.");
        
        $total_price = $shop_item['price'] * $count;
        if ($me['point'] < $total_price) throw new Exception("포인트가 부족합니다.");

        // 포인트 차감
        sql_exec("UPDATE School_Members SET point = point - ? WHERE id = ?", [$total_price, $my_id]);
        
        // 인벤토리 추가 (기존 아이템 있는지 확인)
        $inven = sql_fetch("SELECT id FROM School_Inventory WHERE owner_id = ? AND item_id = ?", [$my_id, $item_id]);
        if ($inven) {
            sql_exec("UPDATE School_Inventory SET count = count + ? WHERE id = ?", [$count, $inven['id']]);
        } else {
            sql_exec("INSERT INTO School_Inventory (owner_id, item_id, count) VALUES (?, ?, ?)", [$my_id, $item_id, $count]);
        }

        // 재고 차감 (무제한이 아닐 경우)
        if ($shop_item['stock'] != -1) {
            sql_exec("UPDATE School_Shop_Config SET stock = stock - ? WHERE item_id = ?", [$count, $item_id]);
        }

        // 로그 기록
        write_log($my_id, 'SHOP', "{$shop_item['name']} {$count}개 구매 (-{$total_price} P)");

        $pdo->commit();
        echo "<script>alert('구매가 완료되었습니다!'); location.replace('shop.php');</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('구매 실패: " . $e->getMessage() . "'); history.back();</script>";
    }
    exit;
}

$me = sql_fetch("SELECT point FROM School_Members WHERE id = ?", [$my_id]);
$products = sql_fetch_all("SELECT i.*, s.stock FROM School_Shop_Config s JOIN School_Item_Info i ON s.item_id = i.item_id ORDER BY s.id DESC");

// [추가됨] 아이템 타입 한글 표시용 맵
$type_map = [
    'CONSUME' => '소모품',
    'WEAPON' => '무기',
    'HAT' => '모자',
    'FACE' => '얼굴',
    'TOP' => '상의',
    'BOTTOM' => '하의',
    'GLOVES' => '장갑',
    'SHOES' => '신발',
    'ETC' => '기타'
];
?>
<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>매점</title>
        <link
            rel="stylesheet"
            as="style"
            crossorigin="crossorigin"
            href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css"/>
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --primary: #CE5961;
                --secondary: #D67F85;
                --bg: #F0F2F5;
                --point: #AED1D5;
                --white: #fff;
            }
            body {
                font-family: 'Pretendard', sans-serif;
                background: var(--bg);
                margin: 0;
                padding-bottom: 80px;
            }
            .shop-header {
                background: var(--primary);
                color: white;
                padding: 20px;
                position: sticky;
                top: 0;
                z-index: 100;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .shop-title {
                font-size: 20px;
                font-weight: 800;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .my-point {
                background: rgba(0,0,0,0.2);
                padding: 8px 15px;
                border-radius: 20px;
                font-weight: bold;
                font-size: 14px;
            }
            .back-btn {
                color: white;
                text-decoration: none;
                font-size: 20px;
                opacity: 0.8;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .product-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }
            .product-card {
                background: white;
                border-radius: 16px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: 0.2s;
                box-shadow: 0 4px 15px rgba(0,0,0,0.03);
                border: 2px solid transparent;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 220px;
            }
            .product-card:active {
                transform: scale(0.98);
            }
            .product-card:hover {
                border-color: var(--point);
                transform: translateY(-5px);
            }
            .p-icon {
                font-size: 40px;
                margin-bottom: 10px;
                color: var(--secondary);
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .p-name {
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 5px;
                color: #333;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .p-price {
                color: var(--primary);
                font-weight: 800;
                font-size: 15px;
                margin-bottom: 10px;
            }
            .p-tag {
                display: inline-block;
                font-size: 11px;
                padding: 3px 8px;
                border-radius: 6px;
                background: #eee;
                color: #777;
                margin-bottom: 5px;
            }
            .modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                align-items: center;
                justify-content: center;
            }
            .modal-box {
                background: white;
                width: 90%;
                max-width: 350px;
                padding: 30px;
                border-radius: 20px;
                text-align: center;
                position: relative;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                animation: slideUp 0.3s ease-out;
            }
            @keyframes slideUp {
                from {
                    transform: translateY(20px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            .m-icon {
                font-size: 50px;
                color: var(--primary);
                margin-bottom: 15px;
            }
            .m-name {
                font-size: 22px;
                font-weight: 800;
                margin-bottom: 10px;
            }
            .m-desc {
                font-size: 14px;
                color: #666;
                margin-bottom: 20px;
                line-height: 1.5;
                background: #f8f8f8;
                padding: 10px;
                border-radius: 8px;
            }
            .m-stats {
                display: flex;
                gap: 5px;
                justify-content: center;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }
            .stat-badge {
                font-size: 12px;
                padding: 5px 8px;
                border-radius: 5px;
                font-weight: bold;
            }
            .st-atk {
                background: #ffebeb;
                color: #c0392b;
            }
            .st-def {
                background: #e8f6f3;
                color: #16a085;
            }
            .st-heal {
                background: #fcf3cf;
                color: #f39c12;
            }
            .st-etc {
                background: #ebf5fb;
                color: #2980b9;
            }
            .count-control {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }
            .count-btn {
                width: 35px;
                height: 35px;
                border-radius: 50%;
                border: 2px solid #ddd;
                background: white;
                font-size: 18px;
                cursor: pointer;
            }
            #buy-count {
                font-size: 20px;
                font-weight: bold;
                width: 50px;
                text-align: center;
                border: none;
            }
            .btn-buy {
                width: 100%;
                background: var(--primary);
                color: white;
                padding: 15px;
                border: none;
                border-radius: 12px;
                font-size: 18px;
                font-weight: bold;
                cursor: pointer;
            }
            .btn-buy:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            .total-price-view {
                font-size: 14px;
                color: #777;
                margin-bottom: 10px;
            }
            .close-modal {
                position: absolute;
                top: 15px;
                right: 20px;
                font-size: 24px;
                cursor: pointer;
                color: #aaa;
            }
        </style>
    </head>
    <body>
        <div class="shop-header">
            <a href="index.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div class="shop-title">
                <i class="fa-solid fa-store"></i>
                학교 매점</div>
            <div class="my-point">
                <i class="fa-solid fa-coins"></i>
                <?=number_format($me['point'])?>
                P</div>
        </div>
        <div class="container">
            <div class="product-grid">
                <?php foreach($products as $p): ?>
                <div class="product-card" onclick='openModal(<?=json_encode($p)?>)'>
                    <div>
                        <div class="p-icon"><?=$p['img_icon']?></div>
                        <div class="p-tag"><?= isset($type_map[$p['type']]) ? $type_map[$p['type']] : $p['type'] ?></div>
                        <div class="p-name"><?=$p['name']?></div>
                    </div>
                    <div>
                        <div class="p-price"><?=number_format($p['price'])?>
                            P</div>
                        <?php if($p['stock'] == 0): ?>
                        <span style="color:red; font-size:12px; font-weight:bold;">품절</span>
                    <?php elseif($p['stock'] > 0): ?>
                        <span style="color:#888; font-size:12px;">남은수량:
                            <?=$p['stock']?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if(!$products): ?>
            <div style="text-align:center; padding:50px; color:#999;">
                <i class="fa-solid fa-box-open" style="font-size:50px; margin-bottom:15px;"></i><br>진열된 상품이 없습니다.
            </div>
            <?php endif; ?>
        </div>

        <div id="buy-modal" class="modal-overlay">
            <div class="modal-box">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <div id="m-icon" class="m-icon"></div>
                <div id="m-name" class="m-name"></div>
                <div id="m-desc" class="m-desc"></div>
                <div id="m-stats" class="m-stats"></div>

                <form method="POST">
                    <input type="hidden" name="action" value="buy">
                    <input type="hidden" name="item_id" id="form-item-id">

                    <div class="count-control">
                        <button type="button" class="count-btn" onclick="changeCount(-1)">-</button>
                        <input type="number" name="count" id="buy-count" value="1" readonly="readonly">
                        <button type="button" class="count-btn" onclick="changeCount(1)">+</button>
                    </div>

                    <div class="total-price-view">총
                        <b id="total-price" style="color:var(--primary)">0</b>
                        P</div>
                    <button type="submit" class="btn-buy" id="btn-submit">구매하기</button>
                </form>
            </div>
        </div>

        <script>
            let currentItem = null;
            let currentCount = 1;
            const myPoint = <?=$me['point']?>; function openModal(item) {
                if (item.stock == 0) {
                    alert("품절된 상품입니다.");
                    return;
                }

                currentItem = item;
                currentCount = 1;

                document
                    .getElementById('buy-modal')
                    .style
                    .display = 'flex';
                document
                    .getElementById('m-icon')
                    .innerHTML = item.img_icon;
                document
                    .getElementById('m-name')
                    .textContent = item.name;
                document
                    .getElementById('m-desc')
                    .textContent = item.descr;
                document
                    .getElementById('form-item-id')
                    .value = item.item_id;

                const statsDiv = document.getElementById('m-stats');
                statsDiv.innerHTML = '';
                try {
                    const eff = JSON.parse(item.effect_data || '{}');
                    if (eff.atk) 
                        statsDiv.innerHTML += `<span class="stat-badge st-atk">공격 +${eff.atk}</span>`;
                    if (eff.def) 
                        statsDiv.innerHTML += `<span class="stat-badge st-def">방어 +${eff.def}</span>`;
                    if (eff.hp_heal) 
                        statsDiv.innerHTML += `<span class="stat-badge st-heal">회복 +${eff.hp_heal}</span>`;
                    
                    // [추가] 기타 스텟 표시
                    if (eff.str) 
                        statsDiv.innerHTML += `<span class="stat-badge st-etc">힘 +${eff.str}</span>`;
                    if (eff.speed) 
                        statsDiv.innerHTML += `<span class="stat-badge st-etc">속도 +${eff.speed}</span>`;
                    if (eff.luk) 
                        statsDiv.innerHTML += `<span class="stat-badge st-etc">행운 +${eff.luk}</span>`;
                    }
                catch (e) {}

                updatePrice();
            }

            function closeModal() {
                document
                    .getElementById('buy-modal')
                    .style
                    .display = 'none';
            }

            function changeCount(delta) {
                let newCount = currentCount + delta;
                if (newCount < 1) 
                    newCount = 1;
                if (currentItem.stock != -1 && newCount > currentItem.stock) {
                    alert("재고가 부족합니다.");
                    return;
                }
                currentCount = newCount;
                updatePrice();
            }

            function updatePrice() {
                const total = currentItem.price * currentCount;
                document
                    .getElementById('buy-count')
                    .value = currentCount;
                document
                    .getElementById('total-price')
                    .textContent = total.toLocaleString();

                const btn = document.getElementById('btn-submit');
                if (myPoint <= 0) {
                    btn.textContent = "포인트 없음";
                    btn.disabled = true;
                } else if (total > myPoint) {
                    btn.textContent = "포인트 부족";
                    btn.disabled = true;
                } else {
                    btn.textContent = "구매하기";
                    btn.disabled = false;
                }
            }

            // 모달 밖 클릭 시 닫기 document.getElementById('buy-modal').addEventListener('click',
            document.getElementById('buy-modal').addEventListener('click', (e) => {
                    if(e.target === document.getElementById('buy-modal')) closeModal();
                });
        </script>
    </body>
</html>
<?php
session_start();

// Tách nhóm ký tự và loại bỏ ký tự dễ nhìn nhầm (0, O, o, 1, I, l)
$upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
$lower = 'abcdefghijkmnpqrstuvwxyz';
$digits = '23456789';
$all = $upper . $lower . $digits;

// Đảm bảo mã 5 ký tự luôn có đủ cả chữ hoa, chữ thường và số
$chars = [
    $upper[random_int(0, strlen($upper) - 1)],
    $lower[random_int(0, strlen($lower) - 1)],
    $digits[random_int(0, strlen($digits) - 1)],
    $all[random_int(0, strlen($all) - 1)],
    $all[random_int(0, strlen($all) - 1)],
];

// Trộn ngẫu nhiên vị trí các ký tự
for ($i = count($chars) - 1; $i > 0; $i--) {
    $j = random_int(0, $i);
    [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
}
$code = implode('', $chars);

// Lưu mã vào session để đối chiếu chính xác hoa/thường
$_SESSION['captcha_code'] = $code;

// Xuất ảnh dạng SVG đồng bộ tông màu nâu kem của BookStory
header('Content-Type: image/svg+xml');
echo '<svg xmlns="http://www.w3.org/2000/svg" width="130" height="44" viewBox="0 0 130 44">';
echo '<rect width="130" height="44" rx="6" fill="#f4efe9" stroke="#d6c7b8" stroke-width="1.5"/>';

// Vẽ các đường nhiễu nhẹ
for ($i = 0; $i < 4; $i++) {
    $x1 = random_int(0, 130);
    $y1 = random_int(0, 44);
    $x2 = random_int(0, 130);
    $y2 = random_int(0, 44);
    echo "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='#c8b6a6' stroke-width='1.2'/>";
}

// Vẽ 5 ký tự nghiêng ngẫu nhiên
$colors = ['#6f4e37', '#8b4513', '#3e2723', '#5d4037', '#a0522d'];
for ($i = 0; $i < strlen($code); $i++) {
    $x = 16 + ($i * 22);
    $y = random_int(26, 31);
    $angle = random_int(-15, 15);
    $color = $colors[array_rand($colors)];
    $char = $code[$i];
    echo "<text x='$x' y='$y' fill='$color' font-family='monospace' font-size='22' font-weight='bold' transform='rotate($angle $x $y)'>$char</text>";
}

echo '</svg>';

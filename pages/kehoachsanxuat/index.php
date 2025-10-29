<?php
require_once(__DIR__ . "/../../class/clskehoachsx.php");

// ✅ Khởi tạo controller/model
$ctrl = new KeHoachModel();

// ✅ Lấy action từ URL (mặc định là 'dsKeHoach')
$action = $_GET['action'] ?? 'dsKeHoach';

switch ($action) {
    case 'dsKeHoach':
        // Hiển thị danh sách kế hoạch
        $ctrl->hienThiDanhSach();
        break;

    case 'xemChiTiet':
        // Trả JSON chi tiết kế hoạch cho AJAX
        header('Content-Type: application/json; charset=utf-8');
        $ctrl->xemChiTiet();
        break;

    case 'pheDuyet':
        // Xử lý phê duyệt / từ chối (trả JSON)
        header('Content-Type: application/json; charset=utf-8');
        $ctrl->pheDuyet();
        break;

    default:
        // Nếu action không hợp lệ
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Hành động không hợp lệ']);
        break;
}
?>

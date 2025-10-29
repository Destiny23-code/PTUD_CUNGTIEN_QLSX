<?php
require_once("clsconnect.php");

class clsLapPYCNL extends ketnoi {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    /* -------------------------------------------------
     🧩 1️⃣ LẤY DANH SÁCH NGUYÊN LIỆU ĐẠT CHUẨN
    ------------------------------------------------- */
    public function getNguyenLieu() {
        $sql = "SELECT maNL, tenNL, moTa, dinhMuc, donViTinh, soLuongTon
                FROM nguyenlieu
                WHERE trangThai = 'Đạt'";
        return $this->laydulieu($this->conn, $sql);
    }

    /* -------------------------------------------------
     🧩 2️⃣ LẤY DANH SÁCH KẾ HOẠCH SẢN XUẤT
    ------------------------------------------------- */
    public function getKeHoachSanXuat() {
        $sql = "SELECT k.maKHSX, k.maDH, d.trangThai AS trangThaiDH
                FROM kehoachsanxuat k
                JOIN donhang d ON k.maDH = d.maDH
                ORDER BY k.maKHSX DESC";
        return $this->laydulieu($this->conn, $sql);
    }

    /* -------------------------------------------------
     🧩 3️⃣ LẤY DANH SÁCH DÂY CHUYỀN THEO XƯỞNG
    ------------------------------------------------- */
    public function getDayChuyenTheoXuong($maXuong) {
        $sql = "SELECT maDC, tenDC
                FROM daychuyen
                WHERE maXuong = $maXuong AND trangThai = 'Hoạt động'";
        return $this->laydulieu($this->conn, $sql);
    }

    /* -------------------------------------------------
     🧩 4️⃣ LẤY NGUYÊN LIỆU THEO KẾ HOẠCH SẢN XUẤT VÀ XƯỞNG
         (Dữ liệu dựa trên bảng khsx_chitiet_nguyenlieu)
    ------------------------------------------------- */
    public function getNguyenLieuTheoKeHoach($maKHSX, $maXuong) {
        $sql = "SELECT n.maNL, n.tenNL, n.donViTinh, n.soLuongTon,
                       d.tenDC, d.maDC,
                       k.tongSLCan AS soLuongCan
                FROM khsx_chitiet_nguyenlieu k
                JOIN nguyenlieu n ON k.maNL = n.maNL
                JOIN daychuyen d ON d.maXuong = $maXuong
                WHERE k.maKHSX = $maKHSX";
        return $this->laydulieu($this->conn, $sql);
    }

    /* -------------------------------------------------
     🧩 5️⃣ THÊM PHIẾU YÊU CẦU NGUYÊN LIỆU + CHI TIẾT
    ------------------------------------------------- */
    public function insertPhieuYeuCau($nguoiLap, $maXuong, $details) {
        try {
            $this->conn->begin_transaction();

            // Tạo phiếu mới
            $sqlPhieu = "INSERT INTO phieuyeucaunguyenlieu (ngayLap, nguoiLap, maXuong, trangThai)
                         VALUES (CURDATE(), $nguoiLap, $maXuong, 'Chờ duyệt')";
            $this->conn->query($sqlPhieu);
            $maPYCNL = $this->conn->insert_id;

            // Thêm từng nguyên liệu
            foreach ($details as $item) {
                $maKH = intval($item['maKH']);
                $maNL = intval($item['maNL']);
                $maDC = intval($item['maDC']);
                $soLuong = floatval($item['soLuongYeuCau']);

                $sqlCT = "INSERT INTO chitietphieuyeucaunguyenlieu (maPYCNL, maKH, maNL, maDC, soLuongYeuCau)
                          VALUES ($maPYCNL, $maKH, $maNL, $maDC, $soLuong)";
                $this->conn->query($sqlCT);

                // Trừ tồn kho tạm (nếu có)
                $sqlUpd = "UPDATE nguyenlieu 
                           SET soLuongTon = soLuongTon - $soLuong
                           WHERE maNL = $maNL AND soLuongTon >= $soLuong";
                $this->conn->query($sqlUpd);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Lỗi insertPhieuYeuCau: " . $e->getMessage());
            return false;
        }
    }

    /* -------------------------------------------------
     🧩 6️⃣ LẤY DANH SÁCH TẤT CẢ CÁC PHIẾU YÊU CẦU
    ------------------------------------------------- */
    public function getAllPhieuYeuCau() {
        $sql = "SELECT p.maPYCNL, p.ngayLap, 
                       nv.tenNV AS nguoiLap, 
                       x.tenXuong, 
                       p.trangThai
                FROM phieuyeucaunguyenlieu p
                LEFT JOIN nhanvien nv ON p.nguoiLap = nv.maNV
                LEFT JOIN xuong x ON p.maXuong = x.maXuong
                ORDER BY p.maPYCNL DESC";
        return $this->laydulieu($this->conn, $sql);
    }

    /* -------------------------------------------------
     🧩 7️⃣ LẤY CHI TIẾT 1 PHIẾU YÊU CẦU NGUYÊN LIỆU
    ------------------------------------------------- */
    public function getChiTietPhieu($maPYCNL) {
        $sql = "SELECT c.maCTPYCNL, 
                       n.tenNL, 
                       s.tenSP, 
                       d.tenDC, 
                       c.soLuongYeuCau
                FROM chitietphieuyeucaunguyenlieu c
                JOIN nguyenlieu n ON c.maNL = n.maNL
                JOIN kehoachsanxuat k ON c.maKH = k.maKHSX
                JOIN daychuyen d ON c.maDC = d.maDC
                JOIN sanpham s ON k.maDH = s.maSP
                WHERE c.maPYCNL = $maPYCNL";
        return $this->laydulieu($this->conn, $sql);
    }
}
?>

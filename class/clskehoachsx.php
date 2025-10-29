<?php
require_once("clsconnect.php"); 

class KeHoachModel extends ketnoi {
    /**
     * Lấy danh sách Đơn hàng chờ lập kế hoạch.
     */
    public function getDSDonHangCho() {
        $link = $this->connect();
        $sql = "SELECT 
                d.maDH, 
                d.ngayDat, 
                d.ngayGiaoDuKien, 
                d.trangThai
            FROM DONHANG d
            WHERE d.trangThai = N'Mới tạo'
            ORDER BY d.ngayDat DESC";
        
        $data = $this->laydulieu($link, $sql);
        $link->close();
        return $data;
    }

    /**
     * Lấy danh sách sản phẩm theo mã đơn hàng
     */
    public function getSanPhamTheoDonHang($maDH) {
        $link = $this->connect();
        $maDH_safe = $link->real_escape_string($maDH);

        $sql = "SELECT 
                    c.maSP, s.tenSP, s.loaiSP, s.donViTinh, s.moTa, c.soLuong
                FROM CHITIET_DONHANG c
                JOIN SANPHAM s ON c.maSP = s.maSP
                WHERE c.maDH = '$maDH_safe'";

        $data = $this->laydulieu($link, $sql);
        $link->close();
        return is_array($data) ? $data : [];
    }

    /**
     * Lấy chi tiết đơn hàng (thông tin + sản phẩm + khách hàng)
     */
    public function getChiTietDonHang($maDH) {
        $link = $this->connect();
        $maDH_safe = $link->real_escape_string($maDH);

        $sqlInfo = "SELECT 
                        d.maDH, d.ngayDat, d.ngayGiaoDuKien, d.trangThai, 
                        d.nguoiPhuTrach, d.ghiChu,
                        kh.tenKH, kh.diaChi, kh.email, kh.dienThoai
                    FROM DONHANG d
                    JOIN KHACHHANG kh ON d.maKH = kh.maKH
                    WHERE d.maDH = '$maDH_safe'";
        $thongtin = $this->laydulieu($link, $sqlInfo);
        $thongtin = is_array($thongtin) && count($thongtin) > 0 ? $thongtin[0] : [];

        $sqlSP = "SELECT 
                        c.maSP, s.tenSP, s.loaiSP, s.donViTinh, c.soLuong
                    FROM CHITIET_DONHANG c
                    JOIN SANPHAM s ON c.maSP = s.maSP
                    WHERE c.maDH = '$maDH_safe'";
        $sanpham = $this->laydulieu($link, $sqlSP);

        $link->close();
        return [
            'thongtin' => $thongtin,
            'sanpham'  => $sanpham
        ];
    }

    /**
     * Lấy nguyên liệu theo sản phẩm
     */
    public function getNguyenLieuTheoSanPham($maSP) {
        $link = $this->connect();
        $maSP_safe = $link->real_escape_string($maSP);

        $sql = "SELECT 
                    nlsp.maNL, 
                    nl.tenNL,
                    nl.soLuongTon,
                    nl.dinhMuc,
                    nlsp.soLuongTheoSP,
                    nl.donViTinh
                FROM ng_sp_dh nlsp
                JOIN nguyenlieu nl ON nlsp.maNL = nl.maNL
                WHERE nlsp.maSP = '$maSP_safe'";

        $data = $this->laydulieu($link, $sql);
        $link->close();
        return is_array($data) ? $data : [];
    }

    /**
     * Thêm kế hoạch sản xuất mới
     */
    public function insertKeHoachSX($maDH, $nguoiLap, $ngayLap, $hinhThucSX, $ngayBatDau, $ngayKetThuc, $ghiChu) {
        $link = $this->connect();
        $maDH_safe = $link->real_escape_string($maDH);
        $nguoiLap_safe = $link->real_escape_string($nguoiLap);
        $hinhThucSX_safe = $link->real_escape_string($hinhThucSX);
        $ghiChu_safe = $link->real_escape_string($ghiChu);

        $sql = "INSERT INTO KEHOACHSANXUAT (maDH, nguoiLap, ngayLap, hinhThuc, ngayBDDK, ngayKTDDK, trangThai, ghiChu)
                VALUES (
                    '$maDH_safe', 
                    '$nguoiLap_safe', 
                    '$ngayLap', 
                    N'$hinhThucSX_safe', 
                    '$ngayBatDau', 
                    '$ngayKetThuc', 
                    N'Chờ phê duyệt', 
                    N'$ghiChu_safe'
                )";

        if ($this->xuly($link, $sql)) {
            $maKHSX = $link->insert_id;
            $link->close();
            return $maKHSX;
        } else {
            $link->close();
            return false;
        }
    }

    /**
     * Thêm chi tiết nguyên liệu kế hoạch sản xuất
     */
    public function insertChiTietNguyenLieuKHSX($maKHSX, $maSP, $maNL, $soLuong1SP, $tongSLCan, $slTonTaiKho, $slThieuHut, $phuongAn) {
        $link = $this->connect();

        $maKHSX_safe = $link->real_escape_string($maKHSX);
        $maSP_safe = $link->real_escape_string($maSP);
        $maNL_safe = $link->real_escape_string($maNL);
        $phuongAn_safe = $link->real_escape_string($phuongAn);
        $soLuong1SP_safe = (float)$soLuong1SP;
        $tongSLCan_safe = (float)$tongSLCan;
        $slTonTaiKho_safe = (float)$slTonTaiKho;
        $slThieuHut_safe = (float)$slThieuHut;

        $sql = "INSERT INTO KHSX_CHITIET_NGUYENLIEU (
                    maKHSX, maSP, maNL, soLuong1SP, tongSLCan, slTonTaiKho, slThieuHut, phuongAnXuLy
                ) VALUES (
                    '$maKHSX_safe',
                    '$maSP_safe',
                    '$maNL_safe',
                    $soLuong1SP_safe,
                    $tongSLCan_safe,
                    $slTonTaiKho_safe,
                    $slThieuHut_safe,
                    N'$phuongAn_safe'
                )";

        $result = $this->xuly($link, $sql);
        $link->close();
        return $result;
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateTrangThaiDonHang($maDH, $trangThaiMoi) {
        $link = $this->connect();
        $maDH_safe = $link->real_escape_string($maDH);
        $trangThaiMoi_safe = $link->real_escape_string($trangThaiMoi);

        $sql = "UPDATE DONHANG 
                SET trangThai = N'$trangThaiMoi_safe'
                WHERE maDH = '$maDH_safe'";

        $result = $this->xuly($link, $sql);
        $link->close();
        return $result;
    }

    /**
     * ✅ Lấy danh sách kế hoạch sản xuất
     */
    public function getDanhSachKeHoach() {
        $link = $this->connect();
        $sql = "SELECT maKHSX, maDH, nguoiLap, ngayLap, hinhThuc, ngayBDDK, ngayKTDDK, trangThai, ghiChu, lyDoTuChoi
                FROM KEHOACHSANXUAT
                ORDER BY ngayLap DESC";
        $data = $this->laydulieu($link, $sql);
        $link->close();
        return $data;
    }

    /**
     * ✅ Chi tiết kế hoạch sản xuất
     */
    public function getChiTietKeHoach($maKHSX) {
        $link = $this->connect();
        $maKHSX_safe = $link->real_escape_string($maKHSX);

        $sqlKH = "SELECT * FROM KEHOACHSANXUAT WHERE maKHSX = '$maKHSX_safe'";
        $info = $this->laydulieu($link, $sqlKH);
        $info = is_array($info) && count($info) > 0 ? $info[0] : [];

        $sqlSP = "
            SELECT cdh.maSP, s.tenSP, s.donViTinh, cdh.soLuong
            FROM CHITIET_DONHANG cdh
            JOIN SANPHAM s ON cdh.maSP = s.maSP
            WHERE cdh.maDH = '{$info['maDH']}'
        ";
        $dsSanPham = $this->laydulieu($link, $sqlSP);

        $sqlNL = "
            SELECT nlct.maKHSX, nlct.maSP, nlct.maNL,
                   nl.tenNL, nl.donViTinh,
                   nlct.soLuong1SP, nlct.tongSLCan, 
                   nlct.slTonTaiKho, nlct.slThieuHut, 
                   nlct.phuongAnXuLy
            FROM KHSX_CHITIET_NGUYENLIEU nlct
            LEFT JOIN NGUYENLIEU nl ON nlct.maNL = nl.maNL
            WHERE nlct.maKHSX = '$maKHSX_safe'
        ";
        $dsNguyenLieu = $this->laydulieu($link, $sqlNL);

        $link->close();
        return [
            'thongtin' => $info,
            'sanpham' => $dsSanPham,
            'nguyenlieu' => $dsNguyenLieu
        ];
    }

    /**
     * ✅ Duyệt hoặc từ chối kế hoạch
     */
    public function capNhatTrangThaiKeHoach($maKHSX, $trangThai, $lyDo = null) {
        $link = $this->connect();
        $maKHSX_safe = $link->real_escape_string($maKHSX);
        $trangThai_safe = $link->real_escape_string($trangThai);
        $lyDo_safe = $lyDo ? $link->real_escape_string($lyDo) : null;

        if ($trangThai_safe === 'Từ chối') {
            $sql = "UPDATE kehoachsanxuat 
                    SET trangThai = '$trangThai_safe', lyDoTuChoi = '$lyDo_safe'
                    WHERE maKHSX = '$maKHSX_safe'";
        } else {
            $sql = "UPDATE kehoachsanxuat 
                    SET trangThai = '$trangThai_safe', lyDoTuChoi = NULL
                    WHERE maKHSX = '$maKHSX_safe'";
        }

        $result = $link->query($sql);
        $link->close();
        return $result;
    }

    // ✅ Controller-level tiện ích (không tạo model mới để tránh vòng lặp)
    public function getAll() {
        return $this->getDanhSachKeHoach();
    }

    public function hienThiDanhSach() {
        $ds = $this->getDanhSachKeHoach();
        include("pages/menuBGD.php");
    }

    public function xemChiTiet() {
        $maKHSX = $_GET['maKHSX'] ?? '';
        if (empty($maKHSX)) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu mã kế hoạch sản xuất']);
            exit;
        }
        $data = $this->getChiTietKeHoach($maKHSX);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function pheDuyet() {
        $maKHSX = $_POST['maKHSX'] ?? '';
        $hanhDong = $_POST['hanhDong'] ?? '';
        $nguoi = $_POST['nguoi'] ?? '';
        $lyDo = $_POST['lyDo'] ?? null;

        if (empty($maKHSX) || empty($hanhDong) || empty($nguoi)) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu thông tin cần thiết']);
            exit;
        }

        $trangThai = ($hanhDong === 'duyet') ? 'Đã duyệt' : 'Từ chối';
        $kq = $this->capNhatTrangThaiKeHoach($maKHSX, $trangThai, $lyDo);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $kq ? true : false,
            'message' => $kq ? 'Cập nhật trạng thái thành công' : 'Cập nhật thất bại',
            'data' => [
                'maKHSX' => $maKHSX,
                'trangThai' => $trangThai,
                'lyDo' => $lyDo,
                'nguoi' => $nguoi
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
?>

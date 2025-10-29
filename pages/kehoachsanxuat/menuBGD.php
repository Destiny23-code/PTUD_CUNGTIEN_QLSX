<?php
require_once(__DIR__ . "/../../class/clskehoachsx.php");
$ctrl = new KeHoachModel();
$dsKeHoach = $ctrl->getAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HỆ THỐNG QUẢN LÝ SẢN XUẤT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body style="background-color:#f4f6f9;">

<div class="container-fluid p-4 mt-3">
  <div class="card shadow-sm p-4">
    <h4 class="fw-bold text-primary mb-3"><i class="bi bi-list-check me-2"></i>Danh sách kế hoạch sản xuất</h4>
    <table class="table table-bordered align-middle shadow-sm bg-white">
      <thead class="table-primary">
        <tr>
          <th>#</th>
          <th>Mã kế hoạch</th>
          <th>Mã đơn hàng</th>
          <th>Người lập</th>
          <th>Ngày lập</th>
          <th>Trạng thái</th>
          <th>Lý do từ chối</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; foreach($dsKeHoach as $k): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= $k['maKHSX'] ?></td>
          <td><?= $k['maDH'] ?></td>
          <td><?= $k['nguoiLap'] ?></td>
          <td><?= $k['ngayLap'] ?></td>
          <td>
            <span class="badge 
              <?= $k['trangThai']=='Đã duyệt' ? 'bg-success' : 
                  ($k['trangThai']=='Từ chối' ? 'bg-danger' : 'bg-warning text-dark') ?>">
              <?= $k['trangThai'] ?>
            </span>
          </td>
          <td><?= $k['lyDoTuChoi'] ?: '-' ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary" onclick="xemChiTiet(<?= $k['maKHSX'] ?>)">
              <i class="bi bi-eye"></i> Xem
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal chi tiết -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary">Chi tiết kế hoạch</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBody"></div>
      <div class="modal-footer">
        <button class="btn btn-success" onclick="pheDuyet('duyet')"><i class="bi bi-check-circle"></i> Duyệt</button>
        <button class="btn btn-danger" onclick="hienLyDo()"><i class="bi bi-x-circle"></i> Từ chối</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal lý do từ chối -->
<div class="modal fade" id="reasonModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Nhập lý do từ chối</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <textarea id="lyDo" class="form-control" rows="3" placeholder="Nhập lý do..."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" onclick="pheDuyet('tuChoi')">Xác nhận</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentKHSX = null;

function xemChiTiet(maKHSX){
  currentKHSX = maKHSX;
  console.log("🟢 Gọi API:", '../../pages/kehoachsanxuat/index.php?action=xemChiTiet&maKHSX=' + maKHSX);
  fetch('../../pages/kehoachsanxuat/index.php?action=xemChiTiet&maKHSX=' + maKHSX)
    .then(async res => {
      const txt = await res.text();
      console.log("📄 Server trả về:", txt);
      const data = JSON.parse(txt);
      const t = data.thongtin;
      const sp = data.sanpham || [];
      const nl = data.nguyenlieu || [];

      let html = `
        <h5 class="text-primary mb-3">Thông tin kế hoạch</h5>
        <p><b>Mã kế hoạch:</b> ${t.maKHSX}</p>
        <p><b>Mã đơn hàng:</b> ${t.maDH}</p>
        <p><b>Người lập:</b> ${t.nguoiLap}</p>
        <p><b>Ngày lập:</b> ${t.ngayLap}</p>
        <p><b>Trạng thái:</b> ${t.trangThai}</p>

        <hr><h5 class="text-success">Sản phẩm trong đơn hàng</h5>
        <table class="table table-bordered table-sm">
          <thead class="table-secondary">
            <tr><th>Mã SP</th><th>Tên sản phẩm</th><th>Số lượng</th><th>Đơn vị tính</th></tr>
          </thead>
          <tbody>
      `;

      sp.forEach(s => {
        html += `<tr>
          <td>${s.maSP}</td>
          <td>${s.tenSP}</td>
          <td>${s.soLuong}</td>
          <td>${s.donViTinh}</td>
        </tr>`;
      });

      html += `</tbody></table><hr><h5 class="text-success">Nguyên liệu</h5>
        <table class="table table-bordered table-sm">
          <thead class="table-secondary">
            <tr>
              <th>Mã NL</th><th>Tên NL</th><th>SL 1 SP</th><th>Tổng cần</th>
              <th>Tồn kho</th><th>Thiếu</th><th>Phương án xử lý</th>
            </tr>
          </thead><tbody>
      `;

      nl.forEach(n => {
        html += `<tr>
          <td>${n.maNL}</td>
          <td>${n.tenNL || '-'}</td>
          <td>${n.soLuong1SP}</td>
          <td>${n.tongSLCan}</td>
          <td>${n.slTonTaiKho}</td>
          <td>${n.slThieuHut > 0 ? '<span class="text-danger fw-bold">'+n.slThieuHut+'</span>' : '<span class="text-success">Đủ</span>'}</td>
          <td>${n.phuongAnXuLy}</td>
        </tr>`;
      });

      html += `</tbody></table>`;
      document.getElementById('modalBody').innerHTML = html;
      new bootstrap.Modal(document.getElementById('detailModal')).show();
    })
    .catch(err => {
      console.error("🔥 Lỗi fetch:", err);
      alert("Không thể tải dữ liệu chi tiết!");
    });
}


function hienLyDo(){
  new bootstrap.Modal(document.getElementById('reasonModal')).show();
}

function pheDuyet(hanhDong){
  let lyDo = '';
  if (hanhDong === 'tuChoi') {
    lyDo = document.getElementById('lyDo').value.trim();
    if (!lyDo) return alert('Vui lòng nhập lý do từ chối!');
  }

  fetch('../../pages/kehoachsanxuat/index.php?action=pheDuyet', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `maKHSX=${currentKHSX}&hanhDong=${hanhDong}&nguoi=admin&lyDo=${encodeURIComponent(lyDo)}`
  })
  .then(res=>res.json())
  .then(data=>{
    if(data.success){
      alert('Cập nhật thành công!');
      location.reload();
    } else {
      alert('Lỗi khi cập nhật!');
      console.log(data);
    }
  })
  .catch(err => {
    console.error("Lỗi fetch pheDuyet:", err);
    alert("Không thể kết nối đến server!");
  });
}
</script>

</body>
</html>

/* Tiện ích dùng chung phía trình duyệt */
window.PetCare = {
  csrf() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
  },
  money(n) {
    return new Intl.NumberFormat('vi-VN').format(n || 0) + ' đ';
  },
  async getJson(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
    if (!res.ok) throw new Error('Không tải được dữ liệu (' + res.status + ')');
    return res.json();
  },
};

/* Hỏi xác nhận trước các thao tác không hoàn tác được: <form data-confirm="..."> */
document.addEventListener('submit', (e) => {
  const form = e.target;
  const msg = form.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) {
    e.preventDefault();
    return;
  }
  const btn = form.querySelector('button[type="submit"]:not([data-keep])');
  if (btn && !e.defaultPrevented) {
    setTimeout(() => { btn.disabled = true; }, 0);
  }
});

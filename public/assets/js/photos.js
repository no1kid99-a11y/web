/*
 * Ảnh cập nhật trực tiếp trong ca làm.
 *  - Lightbox: bấm vào ảnh có [data-lightbox] để xem lớn, chuyển ảnh bằng phím ← →.
 *  - Form chụp ảnh của nhân viên: [data-photo-upload]
 *      Điện thoại: ô chọn ảnh mở thẳng camera sau (capture="environment").
 *      Laptop: thêm nút dùng webcam (chỉ có trên localhost hoặc HTTPS).
 *      Ảnh được thu nhỏ về 1600px ngay trên trình duyệt rồi mới gửi, nên gửi nhanh cả khi mạng yếu.
 *  - Trang theo dõi của khách: [data-live-feed], hỏi máy chủ vài giây một lần và chèn ảnh mới lên đầu.
 */
(function () {
  'use strict';

  var ESC = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ESC[c]; }); }

  var ICON = {
    check: '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
    trash: '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M10 11v6M14 11v6M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V4h6v3"/></svg>',
  };

  /* Thẻ ảnh dùng chung (trùng bố cục với app/Views/partials/photo_card.php). */
  function card(p, opt) {
    opt = opt || {};
    var del = opt.deleteBase
      ? '<form method="post" action="' + esc(opt.deleteBase + '/' + p.id + '/delete') + '" class="absolute right-2 top-2" data-confirm="Gỡ ảnh này? Khách sẽ không thấy nữa.">' +
        '<input type="hidden" name="_csrf" value="' + esc(opt.csrf) + '">' +
        '<button class="grid h-8 w-8 place-items-center rounded-full bg-white/90 text-red-600 shadow hover:bg-white" title="Gỡ ảnh" aria-label="Gỡ ảnh">' + ICON.trash + '</button></form>'
      : '';
    return '<figure class="photo-card relative" data-photo-id="' + p.id + '">' +
      '<a href="' + esc(p.url) + '" data-lightbox data-label="' + esc(p.label) + '" data-caption="' + esc(p.caption) + '" data-time="' + esc(p.time + ' · ' + p.date) + '" data-by="' + esc(p.by) + '" class="group relative block aspect-[4/3] overflow-hidden rounded-2xl bg-ink-100">' +
      '<img src="' + esc(p.thumb) + '" alt="' + esc(p.label + (p.caption ? ': ' + p.caption : '')) + '" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">' +
      '<span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-semibold text-ink-800 shadow-sm">' + esc(p.label) + '</span>' +
      '<span class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-[11px] font-medium tabular-nums text-white">' + esc(p.time) + '</span></a>' +
      del +
      (p.caption ? '<figcaption class="mt-2 text-[13px] leading-snug text-ink-700">' + esc(p.caption) + '</figcaption>' : '') +
      '</figure>';
  }

  // ------------------------------------------------------------------ Lightbox
  var box = null, items = [], index = 0;

  function buildBox() {
    box = document.createElement('div');
    box.className = 'fixed inset-0 z-[100] hidden flex-col bg-black/90 text-white';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Xem ảnh');
    box.innerHTML =
      '<div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">' +
      '  <span data-lb-count class="tabular-nums text-white/70"></span>' +
      '  <button type="button" data-lb-close class="rounded-full bg-white/10 px-3 py-1.5 font-semibold hover:bg-white/20">Đóng ✕</button>' +
      '</div>' +
      '<div class="relative flex min-h-0 flex-1 items-center justify-center px-2" data-lb-stage>' +
      '  <button type="button" data-lb-prev class="absolute left-2 z-10 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl hover:bg-white/25" aria-label="Ảnh trước">‹</button>' +
      '  <img data-lb-img class="max-h-full max-w-full rounded-lg object-contain" alt="">' +
      '  <button type="button" data-lb-next class="absolute right-2 z-10 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl hover:bg-white/25" aria-label="Ảnh sau">›</button>' +
      '</div>' +
      '<div class="mx-auto w-full max-w-3xl px-5 pb-6 pt-3 text-center">' +
      '  <p class="text-sm"><span data-lb-label class="rounded-full bg-white/15 px-2.5 py-0.5 font-semibold"></span> <span data-lb-time class="ml-1 text-white/70"></span></p>' +
      '  <p data-lb-caption class="mt-2 text-[15px] leading-relaxed text-white/90"></p>' +
      '</div>';
    document.body.appendChild(box);
    box.addEventListener('click', function (e) {
      if (e.target.closest('[data-lb-close]') || e.target === box || e.target.hasAttribute('data-lb-stage')) close();
      else if (e.target.closest('[data-lb-prev]')) go(-1);
      else if (e.target.closest('[data-lb-next]')) go(1);
    });
    document.addEventListener('keydown', function (e) {
      if (box.classList.contains('hidden')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') go(-1);
      if (e.key === 'ArrowRight') go(1);
    });
  }

  function show() {
    var a = items[index];
    box.querySelector('[data-lb-img]').src = a.href;
    box.querySelector('[data-lb-img]').alt = a.dataset.label || '';
    box.querySelector('[data-lb-label]').textContent = a.dataset.label || '';
    box.querySelector('[data-lb-time]').textContent = (a.dataset.time || '') + (a.dataset.by ? ' · ' + a.dataset.by : '');
    box.querySelector('[data-lb-caption]').textContent = a.dataset.caption || '';
    box.querySelector('[data-lb-count]').textContent = (index + 1) + ' / ' + items.length;
    box.querySelector('[data-lb-prev]').hidden = items.length < 2;
    box.querySelector('[data-lb-next]').hidden = items.length < 2;
  }
  function go(d) { index = (index + d + items.length) % items.length; show(); }
  function close() { box.classList.add('hidden'); box.classList.remove('flex'); document.body.style.overflow = ''; }

  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[data-lightbox]');
    if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
    e.preventDefault();
    if (!box) buildBox();
    var scope = a.closest('[data-photo-list]') || document;
    items = Array.prototype.slice.call(scope.querySelectorAll('a[data-lightbox]'));
    // Ảnh lớn ở đầu trang theo dõi trùng với ảnh đầu dòng thời gian: chỉ lấy một lần.
    var seen = {};
    items = items.filter(function (x) { if (seen[x.href]) return false; seen[x.href] = 1; return true; });
    index = Math.max(0, items.map(function (x) { return x.href; }).indexOf(a.href));
    box.classList.remove('hidden');
    box.classList.add('flex');
    document.body.style.overflow = 'hidden';
    show();
  });

  // ------------------------------------------------------------------ Thu nhỏ ảnh trước khi gửi
  function shrink(blob, max, quality) {
    return new Promise(function (resolve) {
      var img = new Image();
      var u = URL.createObjectURL(blob);
      img.onload = function () {
        URL.revokeObjectURL(u);
        var w = img.naturalWidth, h = img.naturalHeight;
        var s = Math.min(1, max / Math.max(w, h));
        if (s === 1 && blob.type === 'image/jpeg' && blob.size < 1500000) return resolve(blob);
        var c = document.createElement('canvas');
        c.width = Math.round(w * s);
        c.height = Math.round(h * s);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, 0, 0, c.width, c.height);
        c.toBlob(function (b) { resolve(b || blob); }, 'image/jpeg', quality);
      };
      // Trình duyệt không đọc được (ví dụ HEIC): gửi nguyên file để máy chủ kiểm tra và báo lỗi rõ ràng.
      img.onerror = function () { URL.revokeObjectURL(u); resolve(blob); };
      img.src = u;
    });
  }

  // ------------------------------------------------------------------ Form chụp ảnh (nhân viên / Admin)
  function initUploader(form) {
    var input = form.querySelector('input[type="file"]');
    var preview = form.querySelector('[data-preview]');
    var previewImg = preview.querySelector('img');
    var msg = form.querySelector('[data-msg]');
    var submit = form.querySelector('[data-submit]');
    var caption = form.querySelector('[name="caption"]');
    var list = document.querySelector(form.getAttribute('data-list'));
    var counter = document.querySelector(form.getAttribute('data-counter'));
    var stages = Array.prototype.slice.call(form.querySelectorAll('input[name="stage"]'));
    var blob = null, previewUrl = null;

    function say(text, kind) {
      msg.hidden = !text;
      msg.textContent = text || '';
      msg.className = 'rounded-xl px-3 py-2 text-sm ' + ({
        error: 'bg-red-50 text-red-700', success: 'bg-emerald-50 text-emerald-800', info: 'bg-ink-50 text-ink-700',
      }[kind] || '');
    }
    function setBlob(b) {
      blob = b;
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = b ? URL.createObjectURL(b) : null;
      previewImg.src = previewUrl || '';
      preview.hidden = !b;
      submit.disabled = !b;
      if (b) say('', '');
    }

    input.addEventListener('change', function () { if (input.files && input.files[0]) setBlob(input.files[0]); });
    form.querySelector('[data-clear]').addEventListener('click', function () { input.value = ''; setBlob(null); });
    submit.disabled = true;

    // Webcam: getUserMedia chỉ chạy trên localhost hoặc HTTPS. Trên điện thoại đã có ô chụp ảnh ở trên.
    var camBtn = form.querySelector('[data-webcam]');
    var camPanel = form.querySelector('[data-webcam-panel]');
    var video = camPanel && camPanel.querySelector('video');
    var stream = null;
    function stopCam() {
      if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
      stream = null;
      if (camPanel) camPanel.hidden = true;
    }
    if (camBtn && navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.isSecureContext) {
      camBtn.hidden = false;
      camBtn.addEventListener('click', function () {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1600 } }, audio: false })
          .then(function (s) { stream = s; video.srcObject = s; camPanel.hidden = false; return video.play(); })
          .catch(function () { say('Không mở được webcam. Hãy cho phép trình duyệt dùng camera, hoặc chọn ảnh có sẵn.', 'error'); });
      });
      camPanel.querySelector('[data-snap]').addEventListener('click', function () {
        if (!video.videoWidth) return;
        var c = document.createElement('canvas');
        c.width = video.videoWidth;
        c.height = video.videoHeight;
        c.getContext('2d').drawImage(video, 0, 0);
        c.toBlob(function (b) { if (b) { setBlob(b); stopCam(); } }, 'image/jpeg', 0.88);
      });
      camPanel.querySelector('[data-webcam-close]').addEventListener('click', stopCam);
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!blob) { say('Hãy chụp hoặc chọn một ảnh trước.', 'error'); return; }
      submit.disabled = true;
      say('Đang gửi ảnh cho khách...', 'info');
      shrink(blob, 1600, 0.85).then(function (small) {
        var fd = new FormData();
        fd.append('_csrf', form.querySelector('[name="_csrf"]').value);
        var st = stages.filter(function (s) { return s.checked; })[0];
        fd.append('stage', st ? st.value : 'other');
        fd.append('caption', caption.value);
        fd.append('photo', small, 'anh-cap-nhat.jpg');
        return fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } });
      }).then(function (res) {
        return res.json().catch(function () { return null; }).then(function (data) {
          if (!res.ok || !data || !data.ok) {
            throw new Error(data && data.error ? data.error
              : res.status === 419 ? 'Phiên làm việc đã hết hạn. Hãy tải lại trang rồi chụp lại.'
              : res.status === 413 ? 'Ảnh quá lớn so với cấu hình máy chủ.'
              : 'Không gửi được ảnh (mã lỗi ' + res.status + '). Thử lại nhé.');
          }
          return data.photo;
        });
      }).then(function (p) {
        if (list) {
          var empty = list.querySelector('[data-empty]');
          if (empty) empty.remove();
          list.insertAdjacentHTML('afterbegin', card(p, { deleteBase: form.getAttribute('data-delete-base'), csrf: form.querySelector('[name="_csrf"]').value }));
        }
        if (counter) counter.textContent = String((parseInt(counter.textContent, 10) || 0) + 1);
        input.value = '';
        caption.value = '';
        setBlob(null);
        // Gợi ý luôn mốc tiếp theo cho lần chụp sau.
        var i = stages.findIndex(function (s) { return s.checked; });
        if (i >= 0 && i < stages.length - 2) stages[i + 1].checked = true;
        say('Đã gửi lúc ' + p.time + '. Khách đang mở trang theo dõi sẽ thấy sau vài giây.', 'success');
      }).catch(function (err) {
        submit.disabled = !blob;
        say(err.message || 'Không gửi được ảnh.', 'error');
      });
    });
  }

  // ------------------------------------------------------------------ Trang theo dõi của khách
  var STATUS = {
    confirmed:   { cls: 'bg-amber-50 text-amber-800 ring-amber-200', dot: 'bg-amber-500', text: 'Chờ nhân viên bắt đầu' },
    in_progress: { cls: 'bg-red-50 text-red-700 ring-red-200', dot: 'bg-red-500 animate-pulse', text: 'Đang chăm sóc' },
    completed:   { cls: 'bg-emerald-50 text-emerald-800 ring-emerald-200', dot: 'bg-emerald-500', text: 'Đã hoàn thành' },
    cancelled:   { cls: 'bg-ink-100 text-ink-600 ring-ink-200', dot: 'bg-ink-400', text: 'Đã hủy' },
  };

  function initLive(root) {
    var cfg = JSON.parse(root.querySelector('script[type="application/json"]').textContent);
    var photos = cfg.photos.slice();        // mới nhất trước
    var status = cfg.status;
    var hasRecord = cfg.has_record;
    var fresh = {};                          // id ảnh vừa đến, để tô sáng
    var unseen = 0, timer = null, fails = 0;
    var baseTitle = document.title;
    var $ = function (sel) { return root.querySelector(sel); };

    function renderStatus() {
      var s = STATUS[status] || STATUS.confirmed;
      var pill = $('[data-status-pill]');
      pill.className = 'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold ring-1 ' + s.cls;
      pill.innerHTML = '<span class="h-2 w-2 rounded-full ' + s.dot + '"></span>' + s.text;
      $('[data-done]').hidden = status !== 'completed';
      $('[data-record-link]').hidden = !hasRecord;
      $('[data-live-note]').hidden = status === 'completed' || status === 'cancelled';
    }

    function renderHero() {
      var hero = $('[data-hero]');
      var p = photos[0];
      if (!p) {
        var t = status === 'completed' ? ['Ca này không có ảnh', 'Nhân viên không gửi ảnh trong buổi này. Ghi chú sức khỏe vẫn có trong lịch hẹn.']
          : status === 'in_progress' ? ['Nhân viên đang chăm sóc bé', 'Ảnh đầu tiên sẽ hiện ở đây ngay khi nhân viên chụp. Bạn cứ để trang này mở.']
          : ['Chưa bắt đầu', 'Khi nhân viên đến và bấm Bắt đầu, trang tự chuyển sang trực tiếp. Không cần tải lại.'];
        hero.innerHTML = '<div class="grid aspect-[4/3] place-items-center rounded-3xl border-2 border-dashed border-ink-200 bg-paper p-8 text-center sm:aspect-[16/10]">' +
          '<div><div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-white text-tag-500 shadow-sm">' +
          '<svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>' +
          '<p class="mt-4 font-display text-lg font-bold text-ink-900">' + t[0] + '</p>' +
          '<p class="mx-auto mt-1 max-w-sm text-sm text-ink-500">' + t[1] + '</p></div></div>';
        return;
      }
      hero.innerHTML = '<a href="' + esc(p.url) + '" data-lightbox data-label="' + esc(p.label) + '" data-caption="' + esc(p.caption) + '" data-time="' + esc(p.time + ' · ' + p.date) + '" data-by="' + esc(p.by) + '" class="group relative block overflow-hidden rounded-3xl bg-ink-900' + (fresh[p.id] ? ' ring-4 ring-tag-300' : '') + '">' +
        '<img src="' + esc(p.url) + '" alt="' + esc(p.label) + '" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.02] sm:aspect-[16/10]">' +
        '<span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-ink-900 shadow">' +
        (fresh[p.id] ? '<span class="rounded-full bg-tag-500 px-1.5 text-[10px] uppercase text-white">Mới</span>' : '') + esc(p.label) + '</span>' +
        '<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 via-black/30 to-transparent px-5 pb-4 pt-16 text-white">' +
        '<p class="text-xs font-medium text-white/80"><span class="tabular-nums">' + esc(p.time) + '</span> · ' + esc(p.by) + '</p>' +
        (p.caption ? '<p class="mt-1 text-[15px] font-medium leading-snug sm:text-base">' + esc(p.caption) + '</p>' : '') +
        '</div></a>';
    }

    function renderTimeline() {
      var ul = $('[data-timeline]');
      $('[data-count]').textContent = photos.length;
      if (!photos.length) {
        ul.innerHTML = '<li class="py-6 text-center text-sm text-ink-500">Chưa có ảnh nào.</li>';
        return;
      }
      ul.innerHTML = photos.map(function (p) {
        return '<li class="relative flex gap-4 pb-5 last:pb-0">' +
          '<div class="w-12 shrink-0 pt-1 text-right text-xs font-semibold tabular-nums text-ink-500">' + esc(p.time) + '</div>' +
          '<div class="relative flex-1 border-l-2 border-ink-100 pl-5">' +
          '<span class="absolute -left-[7px] top-1.5 h-3 w-3 rounded-full ring-4 ring-white ' + (fresh[p.id] ? 'bg-tag-500' : 'bg-forest-500') + '"></span>' +
          '<div class="flex gap-3 rounded-2xl p-2 transition ' + (fresh[p.id] ? 'bg-tag-50 ring-1 ring-tag-200' : 'hover:bg-paper') + '">' +
          '<a href="' + esc(p.url) + '" data-lightbox data-label="' + esc(p.label) + '" data-caption="' + esc(p.caption) + '" data-time="' + esc(p.time + ' · ' + p.date) + '" data-by="' + esc(p.by) + '" class="block w-24 shrink-0 overflow-hidden rounded-xl bg-ink-100 sm:w-32">' +
          '<img src="' + esc(p.thumb) + '" alt="' + esc(p.label) + '" loading="lazy" class="aspect-[4/3] w-full object-cover"></a>' +
          '<div class="min-w-0 py-0.5"><p class="text-sm font-semibold text-ink-900">' + esc(p.label) +
          (fresh[p.id] ? ' <span class="ml-1 rounded-full bg-tag-500 px-1.5 py-px text-[10px] font-bold uppercase text-white">Mới</span>' : '') + '</p>' +
          (p.caption ? '<p class="mt-0.5 text-sm leading-snug text-ink-600">' + esc(p.caption) + '</p>' : '') +
          '<p class="mt-1 text-xs text-ink-400">' + esc(p.by) + '</p></div></div></div></li>';
      }).join('');
    }

    function renderProgress() {
      var done = {};
      photos.forEach(function (p) { if (!done[p.stage]) done[p.stage] = p.time; });   // giờ của lần chụp gần nhất ở mỗi mốc
      Array.prototype.forEach.call(root.querySelectorAll('[data-stage]'), function (li) {
        var k = li.getAttribute('data-stage');
        var on = !!done[k];
        li.querySelector('[data-stage-dot]').className = 'grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] font-bold ' + (on ? 'bg-forest-500 text-white' : 'bg-ink-100 text-ink-400');
        li.querySelector('[data-stage-dot]').innerHTML = on ? ICON.check : '';
        li.querySelector('[data-stage-time]').textContent = on ? done[k] : '';
        li.classList.toggle('text-ink-400', !on);
      });
    }

    function render() { renderStatus(); renderHero(); renderTimeline(); renderProgress(); }

    function toast(n) {
      var t = $('[data-toast]');
      t.textContent = n === 1 ? 'Có 1 ảnh mới của bé' : 'Có ' + n + ' ảnh mới của bé';
      t.hidden = false;
      clearTimeout(toast.h);
      toast.h = setTimeout(function () { t.hidden = true; }, 4000);
    }

    function schedule(ms) {
      clearTimeout(timer);
      if (status === 'completed' || status === 'cancelled') return;   // ca đã xong: không cần hỏi tiếp
      timer = setTimeout(tick, ms == null ? cfg.poll * 1000 : ms);
    }

    function tick() {
      if (document.hidden) return;        // tab đang ẩn: tạm dừng, mở lại sẽ hỏi ngay
      var after = photos.length ? photos[0].id : 0;
      fetch(cfg.url + '?after=' + after, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
        .then(function (res) {
          var json = (res.headers.get('content-type') || '').indexOf('application/json') >= 0;
          if (!json) throw new Error(res.redirected ? 'login' : 'net');
          return res.json();
        })
        .then(function (d) {
          fails = 0;
          $('[data-offline]').hidden = true;
          if (d.error) throw new Error('net');
          var alive = {};
          d.ids.forEach(function (id) { alive[id] = 1; });
          var before = photos.length;
          photos = photos.filter(function (p) { return alive[p.id]; });   // ảnh bị gỡ
          var incoming = d.photos.filter(function (p) { return !photos.some(function (x) { return x.id === p.id; }); });
          incoming.forEach(function (p) { fresh[p.id] = 1; setTimeout(function () { delete fresh[p.id]; renderTimeline(); renderHero(); }, 8000); });
          photos = incoming.concat(photos);
          var changed = incoming.length || photos.length !== before || d.status !== status || d.has_record !== hasRecord;
          status = d.status;
          hasRecord = d.has_record;
          $('[data-updated]').textContent = d.server_time;
          if (changed) render();
          if (incoming.length) {
            toast(incoming.length);
            if (document.hidden || !document.hasFocus()) {
              unseen += incoming.length;
              document.title = '(' + unseen + ') ' + baseTitle;
            }
          }
          schedule();
        })
        .catch(function (err) {
          fails++;
          var off = $('[data-offline]');
          off.hidden = false;
          off.textContent = err.message === 'login'
            ? 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang và đăng nhập lại.'
            : 'Mất kết nối tới máy chủ, đang thử lại...';
          if (err.message !== 'login') schedule(Math.min(30000, cfg.poll * 1000 * (fails + 1)));
        });
    }

    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) { unseen = 0; document.title = baseTitle; schedule(0); }
    });
    window.addEventListener('focus', function () { unseen = 0; document.title = baseTitle; });

    render();
    if (location.protocol === 'file:') return;   // bản xem trước giao diện tĩnh: không hỏi máy chủ
    schedule();
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('form[data-photo-upload]'), initUploader);
    Array.prototype.forEach.call(document.querySelectorAll('[data-live-feed]'), initLive);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  window.PetPhotos = { card: card };
})();

<?php
$ic = fn(string $d, string $cls = 'h-5 w-5') => '<svg class="' . $cls . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '"/></svg>';
$check = 'M5 13l4 4L19 7';
?>
<div x-data="bookingWizard(<?= e(json_encode($config, JSON_UNESCAPED_UNICODE)) ?>)" class="mx-auto max-w-6xl">
  <!-- Đầu trang -->
  <div class="flex items-start gap-3">
    <a href="<?= url('/') ?>" class="mt-1 grid h-10 w-10 shrink-0 place-items-center rounded-full border border-ink-100 bg-white text-ink-600 hover:text-ink-900" aria-label="Quay lại trang chủ">
      <?= $ic('M15 18l-6-6 6-6') ?>
    </a>
    <div class="flex-1">
      <h1 class="page-title text-2xl sm:text-3xl">Đặt lịch chăm sóc tại nhà</h1>
      <p class="mt-0.5 text-sm text-ink-500">Nhân viên PetCare mang đủ dụng cụ đã khử khuẩn tới tận cửa</p>
    </div>
    <span class="mt-1 shrink-0 rounded-full bg-tag-100 px-3 py-1 text-xs font-bold text-tag-700">Bước <span x-text="currentStep + 1"></span>/4</span>
  </div>

  <!-- Thanh tiến trình -->
  <ol class="mt-6 grid grid-cols-4" aria-label="Các bước đặt lịch">
    <template x-for="(s, i) in steps" :key="i">
      <li class="relative flex flex-col items-center text-center">
        <span class="absolute left-1/2 top-[18px] h-1 w-full rounded" :class="i < 3 ? (done(i) ? 'bg-forest-500' : 'bg-ink-100') : 'hidden'"></span>
        <button type="button" class="relative z-10 grid h-9 w-9 place-items-center rounded-full text-sm font-bold ring-4 ring-paper transition"
                :class="done(i) && i !== currentStep ? 'bg-forest-600 text-white' : (i === currentStep ? 'bg-tag-400 text-white' : 'bg-ink-100 text-ink-400')"
                @click="openStep(i)" :aria-current="i === currentStep ? 'step' : null" :aria-label="s">
          <template x-if="done(i) && i !== currentStep"><?= $ic($check, 'h-4 w-4') ?></template>
          <template x-if="!(done(i) && i !== currentStep)"><span x-text="i + 1"></span></template>
        </button>
        <span class="mt-2 text-xs font-semibold" :class="i === currentStep ? 'text-tag-600' : (done(i) ? 'text-forest-700' : 'text-ink-400')" x-text="s"></span>
      </li>
    </template>
  </ol>

  <form method="post" action="<?= url('/bookings') ?>" class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]" @submit="submitting = true">
    <?= csrf_field() ?>
    <input type="hidden" name="pet_id" :value="petId">
    <input type="hidden" name="booking_date" :value="date">
    <input type="hidden" name="time_slot_id" :value="slotId">
    <template x-if="mode === 'combo'"><input type="hidden" name="combo_id" :value="comboId"></template>
    <template x-if="mode === 'services'"><div><template x-for="sid in serviceIds" :key="sid"><input type="hidden" name="service_ids[]" :value="sid"></template></div></template>

    <div class="space-y-5">
      <!-- 1. Thú cưng -->
      <section class="rounded-3xl border bg-white p-5 shadow-sm sm:p-6" :class="open === 0 ? 'border-tag-300 ring-4 ring-tag-100' : 'border-ink-100'">
        <header class="flex items-center justify-between gap-3">
          <h2 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide" :class="done(0) ? 'text-forest-700' : 'text-ink-800'">
            <span class="grid h-6 w-6 place-items-center rounded-full" :class="done(0) ? 'bg-forest-600 text-white' : 'bg-ink-100 text-ink-500'"><?= $ic($check, 'h-3.5 w-3.5') ?></span>
            1. Thú cưng được chọn
          </h2>
          <button type="button" class="text-sm font-bold text-tag-600 hover:underline" x-show="open !== 0" @click="openStep(0)">Thay đổi</button>
        </header>
        <div x-show="open !== 0 && pet" class="mt-4 flex items-center gap-4">
          <img :src="pet && pet.photo" @error="$event.target.src = pet.fallback" alt="" class="h-16 w-16 rounded-2xl object-cover">
          <div>
            <p class="flex items-center gap-2"><span class="font-display text-xl font-extrabold" x-text="pet && pet.name"></span><span class="rounded-full bg-ink-100 px-2 py-0.5 text-[11px] font-semibold text-ink-600" x-text="pet && pet.speciesLabel"></span></p>
            <p class="text-sm text-ink-500"><span x-text="pet && (pet.breed || pet.speciesLabel)"></span> · <span class="font-semibold text-tag-600" x-text="pet && pet.weight"></span></p>
            <p class="mt-0.5 flex items-center gap-1 text-xs" :class="pet && pet.vaccinated ? 'text-forest-600' : 'text-ink-400'"><?= $ic('M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6z', 'h-3.5 w-3.5') ?><span x-text="pet && pet.vaccinated ? 'Đã có lịch sử tiêm phòng' : 'Chưa có lịch sử tiêm phòng'"></span></p>
          </div>
        </div>
        <div x-show="open === 0" class="mt-4">
          <div class="grid gap-3 sm:grid-cols-2">
            <template x-for="p in cfg.pets" :key="p.id">
              <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 p-3 transition" :class="petId === p.id ? 'border-forest-600 bg-forest-50' : 'border-ink-100 hover:border-ink-300'">
                <input type="radio" class="sr-only" :value="p.id" x-model.number="petId">
                <img :src="p.photo" @error="$event.target.src = p.fallback" alt="" class="h-14 w-14 rounded-xl object-cover">
                <span class="min-w-0">
                  <span class="block font-bold" x-text="p.name"></span>
                  <span class="block truncate text-xs text-ink-500" x-text="(p.breed || p.speciesLabel) + ' · ' + p.weight"></span>
                </span>
              </label>
            </template>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <a href="<?= url('/pets/create') ?>" class="text-sm font-semibold text-ink-500 underline-offset-4 hover:underline">+ Thêm bé khác</a>
            <button type="button" class="btn btn-accent" :disabled="!stepValid(0)" @click="next(0)">Tiếp tục</button>
          </div>
        </div>
      </section>

      <!-- 2. Gói dịch vụ -->
      <section class="rounded-3xl border bg-white p-5 shadow-sm sm:p-6" :class="open === 1 ? 'border-tag-300 ring-4 ring-tag-100' : 'border-ink-100'">
        <header class="flex items-center justify-between gap-3">
          <h2 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide" :class="done(1) ? 'text-forest-700' : 'text-ink-800'">
            <span class="grid h-6 w-6 place-items-center rounded-full" :class="done(1) ? 'bg-forest-600 text-white' : 'bg-ink-100 text-ink-500'"><?= $ic($check, 'h-3.5 w-3.5') ?></span>
            2. Gói dịch vụ đã chọn
          </h2>
          <button type="button" class="text-sm font-bold text-tag-600 hover:underline" x-show="open !== 1" @click="openStep(1)">Đổi gói</button>
        </header>
        <div x-show="open !== 1 && done(1)" class="mt-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="font-display text-xl font-extrabold leading-snug" x-text="itemsTitle"></p>
              <p class="text-xs text-ink-500">Thời gian làm dự kiến: ~<span x-text="duration"></span> phút</p>
            </div>
            <p class="font-display text-xl font-extrabold text-tag-600" x-text="money(total)"></p>
          </div>
          <div class="mt-3 flex flex-wrap gap-2">
            <template x-for="n in itemNames" :key="n"><span class="rounded-full bg-ink-50 px-3 py-1 text-xs font-medium text-ink-700 ring-1 ring-inset ring-ink-100" x-text="n"></span></template>
          </div>
        </div>
        <div x-show="open === 1" class="mt-4">
          <div class="inline-flex rounded-2xl bg-ink-50 p-1 text-sm font-semibold" role="tablist">
            <button type="button" class="rounded-xl px-4 py-1.5" :class="mode === 'combo' ? 'bg-white text-tag-600 shadow-sm' : 'text-ink-500'" @click="mode = 'combo'" role="tab" :aria-selected="mode === 'combo'">Combo tiết kiệm</button>
            <button type="button" class="rounded-xl px-4 py-1.5" :class="mode === 'services' ? 'bg-white text-tag-600 shadow-sm' : 'text-ink-500'" @click="mode = 'services'" role="tab" :aria-selected="mode === 'services'">Dịch vụ lẻ</button>
          </div>
          <p class="mt-2 text-xs text-ink-500">Mỗi lịch hẹn chọn một combo hoặc các dịch vụ lẻ, không trộn hai loại.</p>
          <div x-show="mode === 'combo'" class="mt-4 space-y-3">
            <template x-for="c in cfg.combos" :key="c.id">
              <label class="flex cursor-pointer gap-4 rounded-2xl border-2 p-3 transition" :class="comboId === c.id ? 'border-forest-600 bg-forest-50' : 'border-ink-100 hover:border-ink-300'">
                <input type="radio" class="sr-only" :value="c.id" x-model.number="comboId">
                <img :src="c.photo" @error="$event.target.src = cfg.fallbackDog" alt="" class="h-20 w-20 shrink-0 rounded-xl object-cover">
                <span class="min-w-0 flex-1">
                  <span class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span class="font-bold" x-text="c.name"></span>
                    <span><span class="font-extrabold text-tag-600" x-text="money(c.price)"></span> <span class="text-xs text-ink-400 line-through" x-show="c.retail > c.price" x-text="money(c.retail)"></span></span>
                  </span>
                  <span class="mt-1 block text-xs text-ink-500" x-text="c.services.join(' · ')"></span>
                  <span class="mt-1 block text-xs font-semibold text-forest-600" x-text="'~' + c.duration + ' phút'"></span>
                </span>
              </label>
            </template>
          </div>
          <div x-show="mode === 'services'" class="mt-4 grid gap-2 sm:grid-cols-2">
            <template x-for="s in cfg.services" :key="s.id">
              <label class="flex cursor-pointer items-start gap-3 rounded-2xl border-2 p-3 transition" :class="serviceIds.includes(s.id) ? 'border-forest-600 bg-forest-50' : 'border-ink-100 hover:border-ink-300'">
                <input type="checkbox" class="mt-1 h-4 w-4 rounded border-ink-300 text-forest-600 focus:ring-tag-300" :value="s.id" x-model.number="serviceIds">
                <span class="flex-1">
                  <span class="flex justify-between gap-2"><span class="text-sm font-bold" x-text="s.name"></span><span class="text-sm font-extrabold text-tag-600" x-text="money(s.price)"></span></span>
                  <span class="block text-xs text-ink-500" x-text="s.duration + ' phút'"></span>
                </span>
              </label>
            </template>
          </div>
          <p x-show="tooLong" class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-inset ring-red-200">
            Tổng <span x-text="duration"></span> phút cộng <span x-text="cfg.travel"></span> phút di chuyển vượt khung <span x-text="cfg.slotMinutes"></span> phút. Hãy bớt dịch vụ hoặc đặt thành hai lịch.
          </p>
          <div class="mt-4 flex justify-end"><button type="button" class="btn btn-accent" :disabled="!stepValid(1)" @click="next(1)">Tiếp tục</button></div>
        </div>
      </section>

      <!-- 3. Ngày giờ -->
      <section class="rounded-3xl border bg-white p-5 shadow-sm sm:p-6" :class="open === 2 ? 'border-tag-300 ring-4 ring-tag-100' : 'border-ink-100'">
        <header class="flex items-center justify-between gap-3">
          <h2 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide" :class="done(2) ? 'text-forest-700' : 'text-ink-800'">
            <span class="grid h-6 w-6 place-items-center rounded-full" :class="done(2) ? 'bg-forest-600 text-white' : 'bg-ink-100 text-ink-500'"><?= $ic($check, 'h-3.5 w-3.5') ?></span>
            3. Chọn ngày &amp; giờ hẹn trống
          </h2>
          <span x-show="open === 2" class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-600">Trực tiếp tại nhà</span>
          <button type="button" class="text-sm font-bold text-tag-600 hover:underline" x-show="open !== 2" @click="openStep(2)">Đổi giờ</button>
        </header>
        <div x-show="open !== 2 && done(2)" class="mt-3 flex items-center gap-3 text-sm">
          <span class="grid h-10 w-10 place-items-center rounded-xl bg-forest-50 text-forest-600"><?= $ic('M12 7v5l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z') ?></span>
          <span><span class="block font-bold text-ink-900" x-text="whenLabel"></span><span class="text-xs text-ink-500">Nhân viên được hệ thống tự phân công</span></span>
        </div>
        <div x-show="open === 2" class="mt-3">
          <p class="text-sm text-ink-500">Chỉ hiện khung còn nhân viên rảnh. Cần đặt trước giờ hẹn ít nhất 2 giờ.</p>
          <p class="mt-4 text-sm font-bold text-ink-800">Ngày chăm sóc</p>
          <div class="mt-2 grid grid-cols-2 gap-3">
            <template x-for="d in quickDays.slice(0, 2)" :key="d.value">
              <button type="button" class="rounded-2xl border-2 p-4 text-left transition" :class="date === d.value ? 'border-tag-400 bg-tag-50' : 'border-ink-100 hover:border-ink-300'" @click="setDate(d.value)">
                <span class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wide" :class="date === d.value ? 'text-tag-700' : 'text-ink-500'">
                  <span x-text="d.weekday"></span>
                  <span x-show="date === d.value" class="grid h-5 w-5 place-items-center rounded-full bg-tag-500 text-white"><?= $ic($check, 'h-3 w-3') ?></span>
                </span>
                <span class="mt-1 block font-display text-xl font-extrabold text-ink-900" x-text="d.long"></span>
                <span class="mt-0.5 block text-xs" :class="freeCount[d.value] === 0 ? 'text-red-500' : 'text-forest-600'" x-text="freeLabel(d.value)"></span>
              </button>
            </template>
          </div>
          <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
            <template x-for="d in quickDays.slice(2)" :key="d.value">
              <button type="button" class="min-w-[4.75rem] rounded-2xl border-2 px-3 py-2 text-center" :class="date === d.value ? 'border-tag-400 bg-tag-50' : 'border-ink-100 hover:border-ink-300'" @click="setDate(d.value)">
                <span class="block text-[11px] font-semibold text-ink-500" x-text="d.weekday"></span>
                <span class="block font-bold" x-text="d.short"></span>
                <span class="block text-[10px]" :class="freeCount[d.value] === 0 ? 'text-red-500' : 'text-forest-600'" x-text="freeCount[d.value] === undefined ? '…' : freeCount[d.value] + ' khung'"></span>
              </button>
            </template>
            <label class="flex min-w-[9.5rem] items-center rounded-2xl border-2 border-dashed border-ink-200 px-3">
              <span class="sr-only">Chọn ngày khác</span>
              <input type="date" class="w-full border-0 bg-transparent p-0 text-sm focus:ring-0" :min="cfg.minDate" :max="cfg.maxDate" :value="date" @change="setDate($event.target.value)">
            </label>
          </div>

          <p class="mt-5 text-sm font-bold text-ink-800">Khung giờ nhân viên phục vụ</p>
          <p x-show="loading" class="mt-2 text-sm text-ink-500">Đang kiểm tra khung giờ còn trống…</p>
          <p x-show="error" class="mt-2 text-sm text-red-600" x-text="error"></p>
          <div x-show="!loading && !error" class="mt-2 space-y-4">
            <template x-for="g in slotGroups" :key="g.name">
              <div x-show="g.slots.length">
                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-ink-500">
                  <span class="text-tag-500" x-html="g.icon"></span><span x-text="g.name"></span>
                </p>
                <div class="mt-2 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                  <template x-for="s in g.slots" :key="s.id">
                    <button type="button" class="rounded-2xl border-2 px-3 py-3 text-center transition"
                            :class="!s.available ? 'cursor-not-allowed border-transparent bg-ink-50 text-ink-300' : (slotId === s.id ? 'border-forest-700 bg-forest-700 text-white shadow-md shadow-forest-700/25' : 'border-ink-100 bg-white hover:border-forest-400')"
                            :disabled="!s.available" @click="slotId = s.id" :aria-pressed="slotId === s.id">
                      <span class="block font-display text-lg font-extrabold tabular-nums" :class="!s.available && 'line-through'" x-text="s.label"></span>
                      <span class="mt-0.5 inline-block rounded-full px-2 text-[11px] font-semibold"
                            :class="slotId === s.id ? 'bg-white/20' : (s.available ? 'text-forest-600' : 'text-ink-400')"
                            x-text="slotId === s.id ? 'Đang chọn' : (s.available ? 'Còn ' + s.free_staff + ' nhân viên' : shortReason(s.reason))"></span>
                    </button>
                  </template>
                </div>
              </div>
            </template>
            <p x-show="slots.length && !slots.some(s => s.available)" class="rounded-2xl bg-tag-50 px-4 py-3 text-sm text-ink-700">Ngày này đã kín lịch, hãy chọn ngày khác.</p>
          </div>
          <div class="mt-5 flex justify-end"><button type="button" class="btn btn-accent" :disabled="!stepValid(2)" @click="next(2)">Tiếp tục</button></div>
        </div>
      </section>

      <!-- 4. Địa chỉ -->
      <section class="rounded-3xl border bg-white p-5 shadow-sm sm:p-6" :class="open === 3 ? 'border-tag-300 ring-4 ring-tag-100' : 'border-ink-100'">
        <header class="flex items-center justify-between gap-3">
          <h2 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide" :class="done(3) ? 'text-forest-700' : 'text-ink-800'">
            <span class="grid h-6 w-6 place-items-center rounded-full" :class="done(3) ? 'bg-forest-600 text-white' : 'bg-ink-100 text-ink-500'"><?= $ic($check, 'h-3.5 w-3.5') ?></span>
            4. Địa chỉ &amp; ghi chú
          </h2>
          <button type="button" class="text-sm font-bold text-tag-600 hover:underline" x-show="open !== 3" @click="openStep(3)">Sửa</button>
        </header>
        <div class="mt-4 space-y-4">
          <div class="flex items-start gap-3 rounded-2xl bg-ink-50 p-3 ring-1 ring-inset ring-ink-100">
            <span class="mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white text-tag-600"><?= $ic('M12 21s-7-6.3-7-12a7 7 0 1114 0c0 5.7-7 12-7 12zm0-9a3 3 0 100-6 3 3 0 000 6z', 'h-4 w-4') ?></span>
            <label class="flex-1">
              <span class="block text-xs font-semibold text-ink-500">Địa chỉ nhân viên đến</span>
              <input name="address" x-model="address" required maxlength="255" placeholder="Số nhà, ngõ, đường, quận" class="mt-0.5 w-full border-0 bg-transparent p-0 font-semibold text-ink-900 placeholder:font-normal placeholder:text-ink-300 focus:ring-0" @focus="open = 3">
            </label>
          </div>
          <label class="block">
            <span class="text-sm font-bold text-ink-800">Ghi chú cho nhân viên chăm sóc</span>
            <textarea name="note" x-model="note" rows="3" maxlength="1000" class="input mt-2 bg-tag-50/50 italic" placeholder="Ví dụ: Bé sợ tiếng máy sấy, nhà có thang máy, gửi xe ở tầng hầm…" @focus="open = 3"></textarea>
          </label>
        </div>
      </section>
    </div>

    <!-- Tóm tắt & xác nhận -->
    <aside class="lg:sticky lg:top-24 lg:self-start">
      <div class="rounded-3xl border border-ink-100 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-sm font-extrabold uppercase tracking-wide text-ink-800">Tóm tắt chi phí</p>
        <dl class="mt-4 space-y-2.5 text-sm">
          <div class="flex justify-between gap-3"><dt class="text-ink-500" x-text="itemsTitle || 'Chưa chọn gói'"></dt><dd class="font-semibold" x-text="money(retail)"></dd></div>
          <div class="flex justify-between gap-3"><dt class="text-ink-500">Phí di chuyển nội thành</dt><dd class="font-semibold text-forest-600">Miễn phí (0 đ)</dd></div>
          <div class="flex justify-between gap-3" x-show="discount > 0"><dt class="text-ink-500">Giảm giá combo</dt><dd class="font-semibold text-tag-600" x-text="'−' + money(discount)"></dd></div>
        </dl>
        <div class="mt-4 border-t border-dashed border-ink-200 pt-4">
          <p class="text-sm font-bold text-ink-900">Tổng thanh toán</p>
          <div class="flex items-end justify-between gap-3">
            <p class="text-xs text-ink-500">Trả cho nhân viên sau khi<br>hoàn thành dịch vụ</p>
            <p class="font-display text-[2rem] font-extrabold leading-none text-tag-600" x-text="money(total)"></p>
          </div>
        </div>
        <div class="mt-4 rounded-2xl bg-ink-50 px-3.5 py-3 text-xs text-ink-600 ring-1 ring-inset ring-ink-100" x-show="whenLabel">
          <span class="font-semibold text-ink-900" x-text="pet && pet.name"></span> · <span x-text="whenLabel"></span>
        </div>
      </div>

      <div class="mt-4 flex items-center gap-3 rounded-2xl bg-forest-50 p-4 ring-1 ring-inset ring-forest-100">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-forest-600 text-white"><?= $ic('M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6zm-3 9l2 2 4-4') ?></span>
        <p class="text-xs leading-relaxed text-forest-800"><strong class="block text-sm">Không lo trùng lịch</strong>Mỗi nhân viên chỉ nhận một nhà trong một khung giờ. Hệ thống kiểm tra lại ngay khi bạn bấm xác nhận.</p>
      </div>

      <button type="submit" class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-tag-400 via-tag-500 to-tag-700 px-5 py-4 text-base font-extrabold text-white shadow-lg shadow-tag-500/30 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!canSubmit() || submitting" data-keep>
        <?= $ic('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z') ?>
        <span x-text="submitting ? 'Đang gửi…' : 'Xác nhận đặt lịch (Chờ duyệt)'"></span>
      </button>
      <p class="mt-2 text-center text-xs text-ink-500" x-show="!canSubmit()" x-text="missingHint"></p>
      <p class="mt-2 text-center text-xs text-ink-500" x-show="canSubmit()">Hủy miễn phí trước giờ hẹn ít nhất 2 giờ.</p>
    </aside>
  </form>
</div>

<script>
function bookingWizard(cfg) {
  const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const sun = '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.4-6.4-1.4 1.4M8 16l-1.4 1.4m0-11L8 8m8 8 1.4 1.4M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>';
  const moon = '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 14.5A8 8 0 019.5 4 8 8 0 1020 14.5z"/></svg>';
  return {
    cfg, steps: ['Thú cưng', 'Dịch vụ', 'Giờ hẹn', 'Xác nhận'],
    open: 0, submitting: false,
    petId: cfg.petId, mode: cfg.serviceIds.length ? 'services' : 'combo',
    comboId: cfg.comboId || (cfg.combos[0] ? cfg.combos[0].id : null), serviceIds: cfg.serviceIds,
    date: cfg.date, slotId: null, slots: [], loading: false, error: '', freeCount: {},
    address: cfg.address, note: cfg.note,
    init() {
      if (!cfg.pets.some(p => p.id === this.petId)) this.petId = cfg.pets[0].id;
      if (!cfg.combos.length) this.mode = 'services';
      if (cfg.comboId || cfg.serviceIds.length) this.open = 1;        // đến từ nút "Chọn gói"
      this.$watch('petId', () => { this.slotId = null; this.loadSlots(); this.loadCounts(); });
      this.loadSlots();
      this.loadCounts();
    },
    money(n) { return PetCare.money(n); },
    get pet() { return cfg.pets.find(p => p.id === this.petId); },
    get combo() { return cfg.combos.find(c => c.id === this.comboId); },
    get chosenServices() { return cfg.services.filter(s => this.serviceIds.includes(s.id)); },
    get duration() { return this.mode === 'combo' ? (this.combo ? this.combo.duration : 0) : this.chosenServices.reduce((a, s) => a + s.duration, 0); },
    get total() { return this.mode === 'combo' ? (this.combo ? this.combo.price : 0) : this.chosenServices.reduce((a, s) => a + s.price, 0); },
    get retail() { return this.mode === 'combo' ? (this.combo ? this.combo.retail : 0) : this.total; },
    get discount() { return Math.max(0, this.retail - this.total); },
    get tooLong() { return this.duration > 0 && this.duration + cfg.travel > cfg.slotMinutes; },
    get itemsTitle() { return this.mode === 'combo' ? (this.combo ? this.combo.name : '') : (this.chosenServices.length ? 'Dịch vụ lẻ (' + this.chosenServices.length + ')' : ''); },
    get itemNames() { return this.mode === 'combo' ? (this.combo ? this.combo.services : []) : this.chosenServices.map(s => s.name); },
    get itemsLabel() { return this.mode === 'combo' ? (this.combo ? this.combo.name : '') : this.chosenServices.map(s => s.name).join(', '); },
    get slot() { return this.slots.find(s => s.id === this.slotId); },
    get whenLabel() {
      if (!this.slot) return '';
      const [y, m, d] = this.date.split('-');
      return this.slot.label + ', ' + d + '/' + m + '/' + y;
    },
    get slotGroups() {
      return [
        { name: 'Buổi sáng', icon: sun, slots: this.slots.filter(s => s.start_time < '12:00') },
        { name: 'Buổi chiều', icon: moon, slots: this.slots.filter(s => s.start_time >= '12:00') },
      ];
    },
    get quickDays() {
      const out = [], names = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
      for (let i = 0; i < 7; i++) {
        const d = new Date(); d.setHours(12, 0, 0, 0); d.setDate(d.getDate() + i);
        out.push({
          value: fmt(d),
          weekday: i === 0 ? 'Hôm nay' : i === 1 ? 'Ngày mai' : names[d.getDay()],
          long: d.getDate() + ' tháng ' + (d.getMonth() + 1),
          short: String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0'),
        });
      }
      return out;
    },
    // Dùng chung cho kiểm thử: bước hiện tại hợp lệ chưa
    get step() { return this.open; },
    set step(v) { this.open = v; },
    stepValid(i) {
      if (i === 0) return !!this.pet;
      if (i === 1) return (this.mode === 'combo' ? !!this.combo : this.chosenServices.length > 0) && !this.tooLong;
      if (i === 2) return !!this.slot && this.slot.available;
      if (i === 3) return this.address.trim().length >= 5;
      return false;
    },
    canNext() { return this.stepValid(this.open); },
    done(i) { return this.stepValid(i); },
    get currentStep() { for (let i = 0; i < 4; i++) { if (!this.stepValid(i)) return i; } return 3; },
    canSubmit() { return [0, 1, 2, 3].every(i => this.stepValid(i)); },
    get missingHint() {
      const i = this.currentStep;
      return ['Hãy chọn thú cưng.', 'Hãy chọn gói dịch vụ phù hợp.', 'Hãy chọn một khung giờ còn trống.', 'Hãy nhập địa chỉ đầy đủ.'][i];
    },
    openStep(i) { this.open = i; },
    next(i) { if (this.stepValid(i)) { this.open = Math.min(3, i + 1); } },
    go(i) { if (i > this.open && !this.canNext()) return; this.open = i; },
    shortReason(r) {
      if (!r) return '';
      if (r.startsWith('Cần đặt')) return 'Quá sát giờ';
      return { 'Đã kín lịch': 'Đã kín', 'Thú cưng đã có lịch': 'Bé đã có lịch' }[r] || r;
    },
    freeLabel(v) {
      const n = this.freeCount[v];
      if (n === undefined) return 'Đang kiểm tra…';
      return n === 0 ? 'Hết khung trống' : n + ' khung giờ trống';
    },
    setDate(v) { if (!v) return; this.date = v; this.slotId = null; this.loadSlots(); },
    async loadSlots() {
      this.loading = true; this.error = '';
      try {
        const data = await PetCare.getJson(cfg.slotsUrl + '?date=' + encodeURIComponent(this.date) + '&pet_id=' + this.petId);
        this.slots = data.slots || [];
        this.freeCount[this.date] = this.slots.filter(s => s.available).length;
        if (this.slotId && !this.slots.some(s => s.id === this.slotId && s.available)) this.slotId = null;
      } catch (e) { this.error = e.message; this.slots = []; }
      this.loading = false;
    },
    async loadCounts() {
      await Promise.all(this.quickDays.map(async (d) => {
        try {
          const data = await PetCare.getJson(cfg.slotsUrl + '?date=' + d.value + '&pet_id=' + this.petId);
          this.freeCount[d.value] = (data.slots || []).filter(s => s.available).length;
        } catch (e) { /* bỏ qua, ô ngày vẫn chọn được */ }
      }));
    },
  };
}
</script>

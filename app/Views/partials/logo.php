<?php $light = $light ?? false; ?>
<span class="inline-flex items-center gap-2">
  <svg class="h-8 w-8 shrink-0" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="9" fill="<?= $light ? '#F2B33D' : '#1D2A44' ?>"/><g fill="<?= $light ? '#1D2A44' : '#F2B33D' ?>"><ellipse cx="16" cy="20" rx="6" ry="5"/><circle cx="9" cy="13" r="2.6"/><circle cx="23" cy="13" r="2.6"/><circle cx="13" cy="8.5" r="2.4"/><circle cx="19" cy="8.5" r="2.4"/></g></svg>
  <span class="font-display text-xl font-bold leading-none <?= $light ? 'text-white' : 'text-ink-900' ?>">PetCare<span class="<?= $light ? 'text-tag-300' : 'text-tag-600' ?>"> Home</span></span>
</span>

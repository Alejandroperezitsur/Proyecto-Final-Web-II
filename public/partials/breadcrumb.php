<?php
// $breadcrumbs: array de items ['label' => string, 'url' => string|null]
// Si no se provee, se crea un breadcrumb simple: Inicio > Página actual
if (!isset($breadcrumbs) || !is_array($breadcrumbs) || empty($breadcrumbs)) {
  $pageTitle = $pageTitle ?? '';
  $current = $current ?? ($pageTitle ?: 'Actual');
  $breadcrumbs = [
    ['label' => 'Inicio', 'url' => 'dashboard.php'],
    ['label' => $current, 'url' => null]
  ];
}
?>
<nav aria-label="breadcrumb" class="small">
  <ol class="breadcrumb mb-0">
    <?php foreach ($breadcrumbs as $i => $bc): ?>
      <?php $isLast = $i === (count($breadcrumbs) - 1); ?>
      <?php if (!$isLast && !empty($bc['url'])): ?>
        <li class="breadcrumb-item"><a href="<?= htmlspecialchars($bc['url']) ?>"><?= htmlspecialchars($bc['label']) ?></a></li>
      <?php else: ?>
        <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" aria-current="<?= $isLast ? 'page' : 'false' ?>">
          <?= htmlspecialchars($bc['label']) ?>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
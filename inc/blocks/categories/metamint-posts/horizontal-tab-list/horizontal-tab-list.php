<?php

/**
 * Block Name: Horizontal Tab List
 * Block Category: Posts
 * Block Icon: index-card
 * Block Keywords: horizontal, tab, tabs, list, features, integration, metamint
 * Block Mode: auto
 */

// Preview Image Handling
if (isset($block['data']['is_preview']) && !empty($block['data']['_preview_image_url'])) {
  echo '<img src="' . esc_url($block['data']['_preview_image_url']) . '" style="width:100%; height:auto; display:block;" alt="Block Preview">';
  return;
}

// Fields
$htl_subtitle = get_field('htl_subtitle') ?: '';
$htl_title = get_field('htl_title') ?: '';
$htl_description = get_field('htl_description') ?: '';
$htl_items = get_field('htl_items') ?: [];

// Color Theme: htl_color_theme (Choices: Green, Red)
$htl_theme = get_field('htl_color_theme');
if (is_array($htl_theme)) {
  $htl_theme = $htl_theme['value'] ?? ($htl_theme['label'] ?? '');
}
if (strtolower(trim((string) $htl_theme)) === 'red') {
  $theme_class = 'theme-red';
  $theme_accent = '#f05d5e';
  $theme_accent_d = '#d94142';
} else {
  $theme_class = 'theme-green';
  $theme_accent = '#0f7173';
  $theme_accent_d = '#0b5658';
}

// Background Color: htl_background_color (empty = page background)
$htl_bg = get_field('htl_background_color');

$section_inline_styles = [];
if (!empty($htl_bg)) {
  $section_inline_styles[] = 'background-color: ' . esc_attr(trim((string) $htl_bg));
}
$section_inline_styles[] = '--mm-accent: ' . esc_attr($theme_accent);
$section_inline_styles[] = '--mm-accent-d: ' . esc_attr($theme_accent_d);
$section_style_attr = ' style="' . implode('; ', $section_inline_styles) . ';"';

// Unique id (aria wiring, supports several blocks on one page)
$htl_uid = 'mm-tabs-' . (!empty($block['id']) ? sanitize_html_class($block['id']) : wp_unique_id());
$htl_anchor_attr = !empty($block['anchor']) ? ' id="' . esc_attr($block['anchor']) . '"' : '';
$htl_extra_class = !empty($block['className']) ? ' ' . esc_attr($block['className']) : '';

// Resolve an image field (array / attachment id / url string) to a url
$htl_icon_url = function ($icon) {
  if (is_array($icon)) {
    return $icon['url'] ?? '';
  }
  if (is_numeric($icon)) {
    return (string) wp_get_attachment_url((int) $icon);
  }
  if (is_string($icon)) {
    return trim($icon);
  }
  return '';
};

// Normalise rows
$htl_rows = [];
if (is_array($htl_items)) {
  foreach ($htl_items as $row) {
    $title = trim((string) ($row['htl_item_title'] ?? ''));
    $short = trim((string) ($row['htl_item_short_title'] ?? ''));
    if ($title === '' && $short === '') {
      continue;
    }
    $htl_rows[] = [
      'title' => $title !== '' ? $title : $short,
      'short' => $short !== '' ? $short : $title,
      'desc' => (string) ($row['htl_item_content'] ?? ''),
      'icon' => $htl_icon_url($row['htl_item_icon'] ?? ''),
    ];
  }
}
?>
<section<?= $htl_anchor_attr; ?> class="mm-section mm-tabs-section
  <?= esc_attr($theme_class); ?>
  <?= $htl_extra_class; ?>"
  <?= $section_style_attr; ?> data-testid="horizontal-tab-list">
  <div class="mm-wrap">
    <?php if (!empty($htl_subtitle)): ?>
      <p class="mm-eyebrow">
        <?php echo esc_html($htl_subtitle); ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($htl_title)): ?>
      <h2 class="mm-h2">
        <?php echo esc_html($htl_title); ?>
      </h2>
    <?php endif; ?>

    <?php if (!empty($htl_description)): ?>
      <p class="mm-lede mm-left">
        <?php echo wp_kses_post($htl_description); ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($htl_rows)): ?>
      <div class="mm-tabs">
        <div class="mm-tabs__list" role="tablist" aria-orientation="vertical"
          aria-label="<?php echo esc_attr($htl_title ?: 'Features'); ?>">
          <?php foreach ($htl_rows as $i => $row):
            $is_first = ($i === 0);
            $icon_style = $row['icon'] ? ' style="' . esc_attr('--mm-tabs-icon: url("' . esc_url($row['icon']) . '")') . '"' : '';
            ?>
            <button type="button" class="mm-tabs__tab" role="tab" id="<?= esc_attr($htl_uid . '-tab-' . $i); ?>"
              aria-controls="<?= esc_attr($htl_uid . '-panel-' . $i); ?>"
              aria-selected="<?= $is_first ? 'true' : 'false'; ?>" tabindex="<?= $is_first ? '0' : '-1'; ?>"
              data-index="<?= (int) $i; ?>" data-testid="tabs-tab-<?= (int) $i; ?>">
              <?php if ($row['icon']): ?>
                <span class="mm-tabs__icon" aria-hidden="true" <?= $icon_style; ?>></span>
              <?php endif; ?>
              <span class="mm-tabs__label">
                <?php echo esc_html($row['short']); ?>
              </span>
            </button>
            <?php if ($row['desc'] !== ''): ?>
              <div class="mm-tabs__detail" data-open="<?= $is_first ? 'true' : 'false'; ?>">
                <p>
                  <?php echo nl2br(esc_html($row['desc'])); ?>
                </p>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <div class="mm-tabs__panels">
          <?php foreach ($htl_rows as $i => $row):
            $is_first = ($i === 0);
            $icon_style = $row['icon'] ? ' style="' . esc_attr('--mm-tabs-icon: url("' . esc_url($row['icon']) . '")') . '"' : '';
            ?>
            <div class="mm-tabs__panel" role="tabpanel" id="<?= esc_attr($htl_uid . '-panel-' . $i); ?>"
              aria-labelledby="<?= esc_attr($htl_uid . '-tab-' . $i); ?>" tabindex="0" <?= $is_first ? '' : ' hidden'; ?>>
              <?php if ($row['icon']): ?>
                <div class="mm-tabs__tile" aria-hidden="true">
                  <span class="mm-tabs__icon" <?= $icon_style; ?>></span>
                </div>
              <?php endif; ?>
              <h3 class="mm-h3 mm-tabs__panel-title">
                <?php echo esc_html($row['title']); ?>
              </h3>
              <?php if ($row['desc'] !== ''): ?>
                <p class="mm-muted mm-tabs__panel-text">
                  <?php echo nl2br(esc_html($row['desc'])); ?>
                </p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
  </section>
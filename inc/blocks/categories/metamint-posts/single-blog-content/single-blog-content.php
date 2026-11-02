<?php
/**
 * Block Name: Single blog content
 * Block Category: Posts
 * Block Icon: products
 * Block Keywords: Single blog, single post, metamint
 * Block Mode: auto
 */

// Preview Image Handling
if (isset($block['data']['is_preview']) && !empty($block['data']['_preview_image_url'])) {
  echo '<img src="' . esc_url($block['data']['_preview_image_url']) . '" style="width:100%; height:auto; display:block;" alt="Block Preview">';
  return;
}

// 1. Resolve current post ID
$current_post_id = get_the_ID();
if (!$current_post_id) {
  $current_post_id = get_queried_object_id();
}
if (!$current_post_id && !empty($post_id) && is_numeric($post_id)) {
  $current_post_id = (int) $post_id;
}

$post_obj = $current_post_id ? get_post($current_post_id) : null;

// 2. Fallback: In Gutenberg editor / Site Editor preview or when not on a single post, use the latest published post
if (empty($post_obj) || $post_obj->post_type !== 'post') {
  $recent_posts = get_posts([
    'numberposts' => 1,
    'post_status' => 'publish',
    'post_type'   => 'post',
  ]);
  if (!empty($recent_posts)) {
    $post_obj = $recent_posts[0];
    $current_post_id = $post_obj->ID;
  }
}

if ($post_obj):
  global $post;
  $post = $post_obj;
  setup_postdata($post);

  // Enqueue comment-reply script for threaded comments
  if (comments_open($current_post_id) && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }

  // Logo fallback for thumbnail
  $custom_logo_id  = get_theme_mod('custom_logo');
  $custom_logo_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'medium') : '';

  // Metadata calculations
  $author_id   = get_the_author_meta('ID');
  $author_name = get_the_author() ?: 'Metamint Team';
  $post_date   = get_the_date('M j, Y');
  $categories  = get_the_category();

  // Resolve author avatar / profile image URL
  $author_avatar_url = '';
  if ($author_id) {
    // 1. Check custom ACF user avatar field if available
    if (function_exists('get_field')) {
      $acf_avatar = get_field('user_avatar', 'user_' . $author_id)
                 ?: get_field('avatar', 'user_' . $author_id)
                 ?: get_field('profile_image', 'user_' . $author_id)
                 ?: get_field('author_image', 'user_' . $author_id);

      if (is_array($acf_avatar) && !empty($acf_avatar['url'])) {
        $author_avatar_url = $acf_avatar['url'];
      } elseif (is_string($acf_avatar) && !empty($acf_avatar)) {
        $author_avatar_url = $acf_avatar;
      }
    }

    // 2. Check user meta (e.g. Simple Local Avatars or custom meta)
    if (empty($author_avatar_url)) {
      $meta_avatar = get_user_meta($author_id, 'user_avatar', true) ?: get_user_meta($author_id, 'custom_avatar', true);
      if (!empty($meta_avatar)) {
        if (is_numeric($meta_avatar)) {
          $author_avatar_url = wp_get_attachment_image_url((int) $meta_avatar, 'thumbnail');
        } elseif (is_string($meta_avatar)) {
          $author_avatar_url = $meta_avatar;
        }
      }
    }

    // 3. Check standard WordPress avatar / Gravatar
    if (empty($author_avatar_url)) {
      $wp_avatar_url = get_avatar_url($author_id, ['size' => 80, 'default' => '404']);
      if (!empty($wp_avatar_url)) {
        $author_avatar_url = $wp_avatar_url;
      }
    }
  }

  // Content & Reading Time
  $content_raw = get_the_content();
  $word_count  = str_word_count(wp_strip_all_tags($content_raw));
  $read_time   = max(1, (int) ceil($word_count / 200));

  // Excerpt - Clean without [...] or ellipsis in header
  $post_excerpt = get_the_excerpt();
  if (empty(trim($post_excerpt))) {
    $post_excerpt = wp_trim_words($content_raw, 28, '');
  }
  // Strip any [...] or &hellip; or ellipsis
  $post_excerpt = preg_replace('/\[\s*(&hellip;|…|\.{3})\s*\]|\[\.\.\.\]|(&hellip;|…|\.{3})/i', '', $post_excerpt);
  $post_excerpt = trim($post_excerpt);

  // Parse Table of Contents from <h2> and <h3> tags
  $rendered_content = apply_filters('the_content', $content_raw);
  $toc_items = [];

  $content_with_ids = preg_replace_callback('/<h([2-3])([^>]*)>(.*?)<\/h\1>/i', function ($matches) use (&$toc_items) {
    $level = (int) $matches[1];
    $attrs = $matches[2];
    $title = strip_tags($matches[3]);
    $slug  = sanitize_title($title);

    if (empty($slug)) {
      $slug = 'section-' . (count($toc_items) + 1);
    }

    $toc_items[] = [
      'level' => $level,
      'title' => $title,
      'slug'  => $slug,
    ];

    if (strpos($attrs, 'id=') === false) {
      return sprintf('<h%1$d%2$s id="%3$s">%4$s</h%1$d>', $level, $attrs, esc_attr($slug), $matches[3]);
    }
    return $matches[0];
  }, $rendered_content);

  // Keep Reading Query: 3 posts from same category, excluding current
  $category_ids = !empty($categories) ? wp_list_pluck($categories, 'term_id') : [];
  $related_args = [
    'posts_per_page'      => 3,
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'post__not_in'        => [$current_post_id],
    'ignore_sticky_posts' => true,
  ];
  if (!empty($category_ids)) {
    $related_args['category__in'] = $category_ids;
  }

  $related_query = new WP_Query($related_args);

  // If category has fewer than 3 posts, fallback to latest posts
  if ($related_query->post_count < 3) {
    $fallback_args = [
      'posts_per_page'      => 3,
      'post_type'           => 'post',
      'post_status'         => 'publish',
      'post__not_in'        => [$current_post_id],
      'ignore_sticky_posts' => true,
    ];
    $related_query = new WP_Query($fallback_args);
  }
  ?>

  <article class="mm-single-article">

    <!-- ===================================================================
         SECTION 1: Header (Categories, Title, Excerpt, Meta)
         =================================================================== -->
    <header class="mm-section mm-wrap mm-single-header">
      <div class="mm-single-header-inner mm-narrow">
        <?php if (!empty($categories)): ?>
          <div class="blog-featured-tag mm-single-categories">
            <ul class="post-categories">
              <?php foreach ($categories as $cat): ?>
                <li>
                  <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                    <?php echo esc_html($cat->name); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <h1 class="mm-single-title"><?php the_title(); ?></h1>

        <?php if (!empty($post_excerpt)): ?>
          <p class="mm-single-excerpt"><?php echo esc_html(wp_strip_all_tags($post_excerpt)); ?></p>
        <?php endif; ?>

        <div class="blog-featured-meta mm-single-meta">
          <span class="mm-meta-author">
            <span class="mm-author-avatar">
              <?php if (!empty($author_avatar_url)): ?>
                <img src="<?php echo esc_url($author_avatar_url); ?>" alt="<?php echo esc_attr($author_name); ?>" class="mm-author-avatar-img" onerror="this.style.display='none';if(this.nextElementSibling){this.nextElementSibling.style.display='inline-flex';}">
                <span class="mm-author-icon mm-author-icon--fallback" style="display:none;" aria-hidden="true">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                </span>
              <?php else: ?>
                <span class="mm-author-icon" aria-hidden="true">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                </span>
              <?php endif; ?>
            </span>
            <span class="mm-author-name"><?php echo esc_html($author_name); ?></span>
          </span>
          <span class="mm-meta-dot">·</span>
          <span class="mm-meta-date"><?php echo esc_html($post_date); ?></span>
          <span class="mm-meta-dot">·</span>
          <span class="mm-meta-read"><?php echo esc_html($read_time); ?> min read</span>
        </div>
      </div>
    </header>

    <!-- ===================================================================
         SECTION 2: Featured Thumbnail (max-height: 300px, rounded border)
         =================================================================== -->
    <section class="mm-wrap mm-single-thumbnail-section">
      <div class="mm-single-thumbnail-frame">
        <?php if (has_post_thumbnail()): ?>
          <?php the_post_thumbnail('full', ['class' => 'mm-single-featured-img', 'loading' => 'eager']); ?>
        <?php else: ?>
          <div class="blog-thumbnail-fallback mm-single-thumbnail-fallback">
            <?php if (!empty($custom_logo_url)): ?>
              <img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="mm-thumbnail-logo">
            <?php else: ?>
              <div class="mm-thumbnail-brand">
                <span class="mm-brand-icon" aria-hidden="true">
                  <svg width="42" height="42" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="10" fill="#0F7173" />
                    <path d="M12 28V12L20 20L28 12V28" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
                <span class="mm-brand-name">Metamint<span class="mm-logo-caret">^</span>apps</span>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ===================================================================
         SECTION 3: Main Layout (Left: Table of Contents | Right: Content)
         =================================================================== -->
    <section class="mm-wrap mm-single-body-section">
      <div class="mm-single-layout <?php echo empty($toc_items) ? 'no-toc' : ''; ?>">
        
        <?php if (!empty($toc_items)): ?>
          <!-- Left Column: Table of Contents -->
          <aside class="mm-single-sidebar">
            <div class="mm-toc-card mm-card">
              <p class="mm-toc-eyebrow">Contents</p>
              <h4 class="mm-toc-title">Table of contents</h4>
              <nav class="mm-toc-nav" aria-label="Table of contents">
                <ul class="mm-toc-list">
                  <?php foreach ($toc_items as $item): ?>
                    <li class="mm-toc-item mm-toc-level-<?php echo esc_attr($item['level']); ?>">
                      <a href="#<?php echo esc_attr($item['slug']); ?>" class="mm-toc-link">
                        <?php echo esc_html($item['title']); ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </nav>
            </div>
          </aside>
        <?php endif; ?>

        <!-- Right Column: Main Content -->
        <main class="mm-single-content-column">
          <div class="entry-content mm-prose mm-single-content">
            <?php echo $content_with_ids; ?>
          </div>
        </main>

      </div>
    </section>

  </article>

  <!-- ===================================================================
       SECTION: Comments (Full Container Width Discussion & Reply Section)
       =================================================================== -->
  <?php if (comments_open($current_post_id) || get_comments_number($current_post_id)): ?>
    <section class="mm-section mm-wrap mm-single-comments-section" id="comments">
      <?php
      global $withcomments;
      $withcomments = true;
      comments_template();
      ?>
    </section>
  <?php endif; ?>

  <?php wp_reset_postdata(); ?>

  <!-- ===================================================================
       SECTION 4: Keep Reading (3 Posts from Same Category excluding Current)
       =================================================================== -->
  <?php if ($related_query->have_posts()): ?>
    <section class="mm-section mm-wrap block-blog-grid mm-keep-reading-section">
      <div class="mm-keep-reading-header">
        <p class="mm-eyebrow mm-teal">Related articles</p>
        <h2 class="mm-h2">Keep reading</h2>
      </div>

      <div class="blog-posts-grid mm-grid-3">
        <?php while ($related_query->have_posts()): $related_query->the_post();
          $grid_excerpt = wp_trim_words(get_the_excerpt(), 15, '...');
          $grid_author  = get_the_author() ?: 'Metamint Team';
          $grid_content = get_post_field('post_content', get_the_ID());
          $grid_words   = str_word_count(wp_strip_all_tags($grid_content));
          $grid_read    = max(1, (int) ceil($grid_words / 200));
          ?>
          <article class="blog-card mm-card">
            <div class="blog-card-thumbnail">
              <a href="<?php the_permalink(); ?>">
                <?php if (has_post_thumbnail()): ?>
                  <?php the_post_thumbnail('medium_large'); ?>
                <?php else: ?>
                  <div class="blog-thumbnail-fallback">
                    <?php if (!empty($custom_logo_url)): ?>
                      <img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="mm-thumbnail-logo">
                    <?php else: ?>
                      <div class="mm-thumbnail-brand">
                        <span class="mm-brand-icon" aria-hidden="true">
                          <svg width="32" height="32" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="#0F7173" />
                            <path d="M12 28V12L20 20L28 12V28" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </span>
                        <span class="mm-brand-name">Metamint<span class="mm-logo-caret">^</span>apps</span>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </a>
            </div>

            <div class="blog-card-body">
              <span class="blog-card-tag"><?php the_category(); ?></span>
              <h3 class="mm-h3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              <p style="color:var(--muted);margin:0"><?php echo esc_html(wp_strip_all_tags($grid_excerpt)); ?></p>
              <div class="blog-card-meta"><?php echo esc_html($grid_author); ?> · <?php echo esc_html($grid_read); ?> min read</div>
              <div><a class="btn mm-btn mm-btn-ink" href="<?php the_permalink(); ?>">Read article</a></div>
            </div>
          </article>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
      </div>
    </section>
  <?php endif; ?>

<?php else: ?>
  <div class="mm-wrap mm-section">
    <div class="mm-card mm-empty">
      <p class="mm-muted">No post found.</p>
    </div>
  </div>
<?php endif; ?>
<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @package Metamint
 */

if (post_password_required()) {
    return;
}

$current_post_id = get_the_ID();
$comments_open   = comments_open($current_post_id);
$comment_count   = get_comments_number($current_post_id);
?>

<div class="mm-comments-area">

    <div class="mm-comments-header mm-keep-reading-header">
        <p class="mm-eyebrow mm-teal"><?php esc_html_e('Discussion', 'metamint'); ?></p>
        <h2 class="mm-h2 mm-comments-title">
            <?php
            if (have_comments()) {
                if ($comment_count === 1) {
                    esc_html_e('1 Comment', 'metamint');
                } else {
                    printf(
                        /* translators: %s: number of comments */
                        esc_html(_nx('%s Comment', '%s Comments', $comment_count, 'comments title', 'metamint')),
                        number_format_i18n($comment_count)
                    );
                }
            } else {
                esc_html_e('Join the discussion', 'metamint');
            }
            ?>
        </h2>
    </div>

    <?php if (have_comments()): ?>
        <ol class="mm-comment-list">
            <?php
            wp_list_comments([
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 44,
                'format'      => 'html5',
            ]);
            ?>
        </ol>

        <?php
        the_comments_navigation([
            'prev_text' => '<span class="nav-prev-text">' . __('&larr; Older Comments', 'metamint') . '</span>',
            'next_text' => '<span class="nav-next-text">' . __('Newer Comments &rarr;', 'metamint') . '</span>',
        ]);
        ?>

        <?php if (!$comments_open && $comment_count): ?>
            <p class="mm-no-comments"><?php esc_html_e('Comments are closed.', 'metamint'); ?></p>
        <?php endif; ?>
    <?php endif; // have_comments() ?>

    <?php
    if ($comments_open):
        $commenter = wp_get_current_commenter();
        $req       = get_option('require_name_email');
        $html_req  = $req ? " required='required'" : '';
        $aria_req  = $req ? " aria-required='true'" : '';

        $fields = [
            'author' => sprintf(
                '<p class="comment-form-author">' .
                '<label for="author">%s%s</label>' .
                '<input id="author" name="author" type="text" value="%s" size="30" maxlength="245" autocomplete="name"%s placeholder="%s" />' .
                '</p>',
                esc_html__('Name', 'metamint'),
                $req ? ' <span class="required">*</span>' : '',
                esc_attr($commenter['comment_author']),
                $html_req . $aria_req,
                esc_attr__('Your name', 'metamint')
            ),
            'email' => sprintf(
                '<p class="comment-form-email">' .
                '<label for="email">%s%s</label>' .
                '<input id="email" name="email" type="email" value="%s" size="30" maxlength="100" aria-describedby="email-notes" autocomplete="email"%s placeholder="%s" />' .
                '</p>',
                esc_html__('Email', 'metamint'),
                $req ? ' <span class="required">*</span>' : '',
                esc_attr($commenter['comment_author_email']),
                $html_req . $aria_req,
                esc_attr__('your.email@example.com', 'metamint')
            ),
            'url' => sprintf(
                '<p class="comment-form-url">' .
                '<label for="url">%s</label>' .
                '<input id="url" name="url" type="url" value="%s" size="30" maxlength="200" autocomplete="url" placeholder="%s" />' .
                '</p>',
                esc_html__('Website (optional)', 'metamint'),
                esc_attr($commenter['comment_author_url']),
                esc_attr__('https://yourwebsite.com', 'metamint')
            ),
            'cookies' => sprintf(
                '<p class="comment-form-cookies-consent">' .
                '<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%s />' .
                '<label for="wp-comment-cookies-consent">%s</label>' .
                '</p>',
                empty($commenter['comment_author_email']) ? '' : ' checked="checked"',
                esc_html__('Save my name and email in this browser for the next time I comment.', 'metamint')
            ),
        ];

        comment_form([
            'fields'              => $fields,
            'comment_field'       => sprintf(
                '<p class="comment-form-comment">' .
                '<label for="comment">%s <span class="required">*</span></label>' .
                '<textarea id="comment" name="comment" cols="45" rows="5" required="required" placeholder="%s"></textarea>' .
                '</p>',
                esc_html__('Comment', 'metamint'),
                esc_attr__('Write your thoughts or feedback here...', 'metamint')
            ),
            'title_reply'         => have_comments() ? __('Leave a Reply', 'metamint') : __('Leave a Comment', 'metamint'),
            'title_reply_to'      => __('Leave a Reply to %s', 'metamint'),
            'title_reply_before'  => '<h3 id="reply-title" class="mm-h3 comment-reply-title">',
            'title_reply_after'   => '</h3>',
            'cancel_reply_before' => ' <span class="mm-cancel-reply">',
            'cancel_reply_after'  => '</span>',
            'cancel_reply_link'   => __('Cancel reply', 'metamint'),
            'class_form'          => 'mm-comment-form',
            'class_submit'        => 'btn mm-btn mm-btn-ink mm-comment-submit-btn',
            'submit_button'       => '<button type="submit" name="%1$s" id="%2$s" class="%3$s">%4$s</button>',
            'submit_field'        => '<p class="form-submit">%1$s %2$s</p>',
            'label_submit'        => __('Post Comment', 'metamint'),
        ]);
    endif;
    ?>

</div>

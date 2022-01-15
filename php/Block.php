<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types_array = array( 'public' => true );
		$post_types = get_post_types( $post_types_array );
		$class_name = $attributes['className'];
		$curr_post_id = get_queried_object_id();
		ob_start();

		?>
        <div class="<?php echo $class_name; ?>">
			<h2><?php esc_html_e( 'Post Counts' ); ?></h2>
			<?php if ( $post_types ): ?>
				<ul>
					<?php foreach ( $post_types as $post_type_slug ):
						$post_type_object = get_post_type_object( $post_type_slug  );
						$post_count = wp_count_posts( $post_type_slug );
						$count_string = sprintf(
							_n(
								'There is %d ' . $post_type_object->labels->singular_name . '.',
								'There are %d ' . $post_type_object->labels->name . '.',
								$post_count->publish,
								'site-counts'
							),
							$post_count->publish
						);
					?>
						<li><?php echo $count_string; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p><?php echo sprintf( __( 'The current post ID is %s.', 'site-counts' ), $curr_post_id ); ?></p>

			<?php
			$cat_tag_post_type_array = array( 'post', 'page' );
			$post_status = 'any';
			$start_hour = 9;
			$end_hour = 17;
			$tag = 'foo';
			$category_name = 'baz';
			$max_cat_tag_posts = 5;

			$cat_tag_posts = new WP_Query( array(
				'post_type' => $cat_tag_post_type_array,
				'post_status' => $post_status,
				'ignore_sticky_posts' => true,
				'date_query' => array(
					array(
						'hour' => $start_hour,
						'compare' => '>=',
					),
					array(
						'hour' => $end_hour,
						'compare'=> '<=',
					),
				),
                'tag' => $tag,
                'category_name' => $category_name,
			  	'posts_per_page' => $max_cat_tag_posts + 1 // add 1 extra in case you are on a page being excluded
			) );

			$exclude = array( $curr_post_id );
			// if you are on a page without an ID like a posts or archives page, don't exclude the page
			if ( $curr_post_id === 0 ) {
				$exclude = array();
			}

			$max_found_posts = $max_cat_tag_posts;
			if ($max_cat_tag_posts > $cat_tag_posts->found_posts) {
				$max_found_posts = $cat_tag_posts->found_posts;
			}

			if ( $cat_tag_posts->have_posts() ) :
				$posts_count = 0;
				$cat_tag_posts_string = '';
				while ( $cat_tag_posts->have_posts() && $posts_count < $max_found_posts ) {
					$cat_tag_posts->the_post();
					$current = get_the_ID();
					if ( ! in_array( $current, $exclude ) ) {
						$posts_count++;
						$cat_tag_posts_string .= '<li>' . esc_html__( get_the_title(), 'site-counts' ) . '</li>';
					}
				}
				$posts_count_string = sprintf(
					_n(
						'%d post ',
						'%d posts ',
						$posts_count,
						'site-counts'
					),
					$posts_count
				);
				$cat_tag_string = esc_html__( 'with the tag of ' . $tag .' and the category of ' . $category_name, 'site-counts' )
			?>
				<?php if ( $posts_count > 0 ) : ?>
					<h2><?php echo $posts_count_string; ?><?php echo $cat_tag_string; ?></h2>
					<ul><?php echo $cat_tag_posts_string; ?></ul>
            	<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}

<?php
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
	$parenthandle = 'parent-style'; // This is 'albatross-style' for the Twenty Fifteen theme.
	$theme        = wp_get_theme();
	wp_enqueue_style( $parenthandle,
		get_template_directory_uri() . '/style.css?' . time(),
		array(),  // If the parent theme code has a dependency, copy it to here.
		$theme->parent()->get( 'Version' )
	);
	wp_enqueue_style( 'child-style',
		get_stylesheet_uri(),
		array( $parenthandle ),
		$theme->get( 'Version' ) // This only works if you have Version defined in the style header.
	);
	wp_enqueue_script( 'jquery-blockUI', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js', array('jquery'), '2.7', true );
	wp_enqueue_script( 'child-style', get_stylesheet_directory_uri() . '/script.js?' . time(), array(), '', true );
}

add_action('init', 'start_session', 1);
function start_session() {
	if(!session_id()) {
		session_start();
	}
}

add_action('wp_logout','end_session');
add_action('wp_login','end_session');
add_action('end_session_action','end_session');

function end_session() {
	session_destroy ();
}

add_action('init', function() {
    if ( strpos($_SERVER['REQUEST_URI'], 'cityguide') !== false && !isset( $_SESSION['user_email'] ) ) 
    {
        wp_redirect('/');
        exit;
    }
});

add_filter('walker_nav_menu_start_el', 'change_city_guide_url', 999);
function change_city_guide_url($menu_item) {
    if ( strpos($menu_item, 'cityguide') !== false && !isset($_SESSION['user_email']) ) {
        $menu_item = str_replace(esc_url(home_url('/cityguide/')), 'javascript:CityGuide.signUpForm();', $menu_item);
    }
    return $menu_item;
}


function the_follow_us() {
	?>
	<!-- follow us gallery -->
	<section class="follow-us elementor-section elementor-section-boxed">
		<h4 class="text-center">
			<?php the_field('gallery_header_title'); ?>
		</h4>
		<p class="text-center sub-title">
			<?php the_field('gallery_header_sub_title'); ?>
		</p>
		<div class="gallery elementor-container elementor-column-gap-default">
				<?php
					$images = get_field('gallery_images');
					if( $images ):
						foreach( $images as $image_id ):
				?>
							<div class="item"><?php echo wp_get_attachment_image( $image_id['ID'], 'full' ); ?></div>
				<?php
						endforeach;
					endif;
				?>
				<?php
					$images = get_field('gallery_images');
					if( $images ):
						foreach( $images as $image_id ):
				?>
							<div class="item"><?php echo wp_get_attachment_image( $image_id['ID'], 'full' ); ?></div>
				<?php
						endforeach;
					endif;
				?>
		</div>
		<p class="description text-center">
			<?php the_field('gallery_description'); ?>
		</p>
	</section>
	<?php
}

function the_city_guide() {
	?>
		<section class="guide elementor-section elementor-section-boxed">
			
			<div class="elementor-container elementor-column-gap-default">
				<div class="left-sidebar elementor-column elementor-col-20 elementor-inner-column elementor-element">
					<div class="elementor-widget-wrap elementor-element-populated">
						<?php the_filters(); ?>
					</div>
				</div>
				<div class="list elementor-column elementor-col-80 elementor-inner-column elementor-element">
					<div class="elementor-widget-wrap elementor-element-populated">
						<h4 class="city-name">All</h4>
						<div class="item-list">
							<?php the_list(); ?>
						</div>
					</div>
				</div>
			</div>
		</section>
	<?php
}

function the_filters() {
	global $wpdb;

	$cities			=	$wpdb->get_results("select * from wp_stylebible_cities");
	$categories		=	$wpdb->get_results("select * from wp_stylebible_categories");

	$filters = [
		'city'		=>	$cities,
		'category'	=>	$categories,
		'sort'		=>	[
			(object)['sort_id' => 1, 'sort_name' => 'HIGHEST RATING'],
			(object)['sort_id' => 2, 'sort_name' => 'NEWEST'],
			(object)['sort_id' => 3, 'sort_name' => 'LOWEST PRICE'],
			(object)['sort_id' => 4, 'sort_name' => 'HIGHEST PRICE'],
		]
	];

	?>
		<h5>Filter</h5>
		<?php foreach( $filters as $key => $filter ) : ?>
			<div class="filter <?php echo $key; ?>">
				<h5><?php echo $key; ?></h5>
				<ul>
				<?php
					foreach($filter as $row) :
						$id		=	$key . '_id';
						$name	=	$key . '_name';
				?>
						<li class="<?php echo $key . $row->$id; ?>">
							<a href="javascript:CityGuide.setFilter('<?php echo $key; ?>', <?php echo $row->$id; ?>, '<?php echo $row->$name; ?>');"><?php echo $row->$name; ?></a>
						</li>
				<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	<?php
}

add_action( 'wp_ajax_the_list',			'the_list' );
add_action( 'wp_ajax_nopriv_the_list',	'the_list' );
function the_list() {
	global $wpdb;

	$filters = [
		'city'		=>	0,
		'category'	=>	0,
		'sort'		=>	0,
	];

	$pagination = [
		'limit'		=>	6,
		'current'	=>	1
	];

	if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
		$filters	=	$_POST['filters'];
		$pagination	=	$_POST['pagination'];
	}

	$filter_conds = [];

	if( !empty($filters['city']) ) $filter_conds[] = 'city_id = ' . $filters['city'];
	if( !empty($filters['category']) ) $filter_conds[] = 'cat_id = ' . $filters['category'];

	$query	=	"SELECT
					wp_stylebible_establelishments.*,
					wp_stylebible_sub_categories.sub_cat_name 
				FROM
					( SELECT establelishment_id, city_id, cat_id, sub_cat_id FROM wp_stylebible_match_list" . (!empty($filter_conds) ? " WHERE " . implode(" AND ", $filter_conds) : "") . " GROUP BY establelishment_id ) match_list,
					wp_stylebible_establelishments,
					wp_stylebible_cities,
					wp_stylebible_categories,
					wp_stylebible_sub_categories 
				WHERE
					match_list.establelishment_id = wp_stylebible_establelishments.establelishment_id 
					AND match_list.city_id = wp_stylebible_cities.city_id 
					AND match_list.cat_id = wp_stylebible_categories.category_id 
					AND match_list.sub_cat_id = wp_stylebible_sub_categories.sub_cat_id 
					AND wp_stylebible_establelishments.is_deleted = 'n'
				ORDER BY
					establelishment_id
				LIMIT " . ( ($pagination['current'] - 1) * $pagination['limit'] ) . "," . $pagination['limit'];

	$list = $wpdb->get_results( $query );

	$cnt_query	=	"SELECT
						COUNT(*)
					FROM
						( SELECT establelishment_id, city_id, cat_id, sub_cat_id FROM wp_stylebible_match_list" . (!empty($filter_conds) ? " WHERE " . implode(" AND ", $filter_conds) : "") . " GROUP BY establelishment_id ) match_list,
						wp_stylebible_establelishments,
						wp_stylebible_cities,
						wp_stylebible_categories,
						wp_stylebible_sub_categories 
					WHERE
						match_list.establelishment_id = wp_stylebible_establelishments.establelishment_id 
						AND match_list.city_id = wp_stylebible_cities.city_id 
						AND match_list.cat_id = wp_stylebible_categories.category_id 
						AND match_list.sub_cat_id = wp_stylebible_sub_categories.sub_cat_id 
						AND wp_stylebible_establelishments.is_deleted = 'n'";
	
	$total_cnt = $wpdb->get_var( $cnt_query );
	if( $total_cnt > 0 ) {
	?>
		<div class="items">
			<?php foreach( $list as $item ) : ?>
			<div class="item">
				<div class="img-wrapper">
					<img src="<?php echo esc_url(home_url('/wp-content/uploads/2023/01/rest.jpg')) ?>" alt="">
					<span class="rating">
						<?php echo $item->rating; ?>
						<a href="javascript:CityGuide.vote(<?php echo $item->establelishment_id; ?>);">
							<i aria-hidden="true" class="far fa-heart"></i>
						</a>
					</span>
				</div>
				
				<div class="info">
					<span class="category">
						<?php echo $item->sub_cat_name; ?>
					</span>
					<h4 class="name">
						<?php echo $item->establelishment_name; ?>
					</h4>
					<div class="address">
						<?php echo $item->address; ?>
					</div>
					<div class="detail">
						<?php echo $item->why_we_love_it; ?>
					</div>
					<div class="site">
						<a href="<?php echo $item->website_url; ?>" target="_blank"><?php echo retrieve_url($item->website_url); ?></a>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="pagination">
			<?php the_pagination($total_cnt, $pagination); ?>
		</div>
	<?php
	} else {
	?>
		<div class="no-result">
			<h2>No Establelishment</h2>
		</div>
	<?php
	}
	if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) wp_die();
}

function the_pagination( $total_cnt, $pagination ) {
	$current	=	$pagination['current'];
	$page_num	=	(int)($total_cnt / $pagination['limit']);
	if( $total_cnt % $pagination['limit'] !== 0 ) $page_num++;

	$pagination_nav	=	'<li class="prev-nav ' . ($current == 1 ? 'disabled' : '') . '">
							<a href="javascript:' . ($current == 1 ? 'void(0)' : 'CityGuide.goPage(' . $current - 1 . ')') . ';">
								<i aria-hidden="true" class="fas fa-angle-left"></i>
							</a>
						</li>
						<li class="' . ( $current == 1 ? 'active' : '' ) . '"><a href="javascript:CityGuide.goPage(1);">1</a></li>';

	if( $page_num <= 5 ) {
		for( $i = 2; $i < $page_num; $i++ ) {
			$pagination_nav	.=	'<li class="' . ( $current == $i ? 'active' : '' ) . '"><a href="javascript:CityGuide.goPage(' . $i . ');">' . $i . '</a></li>';
		}
	} else {
		if( $current >= 1 && $current < 4 ) {
			for( $i = 2; $i <= 4; $i++ ) {
				$pagination_nav	.=	'<li class="' . ( $current == $i ? 'active' : '' ) . '"><a href="javascript:CityGuide.goPage(' . $i . ');">' . $i . '</a></li>';
			}
			$pagination_nav .= '<li class="dot">...</li>';
		} else if( $current > $page_num - 3 && $current <= $page_num ) {
			$pagination_nav .= '<li class="dot">...</li>';
			for( $i = $page_num - 3; $i < $page_num; $i++ ) {
				$pagination_nav	.=	'<li class="' . ( $current == $i ? 'active' : '' ) . '"><a href="javascript:CityGuide.goPage(' . $i . ');">' . $i . '</a></li>';
			}
		} else {
			$pagination_nav .= '<li class="dot">...</li>';
			for( $i = ($current - 1); $i <= ($current + 1); $i++ ) {
				$pagination_nav	.=	'<li class="' . ( $current == $i ? 'active' : '' ) . '"><a href="javascript:CityGuide.goPage(' . $i . ');">' . $i . '</a></li>';
			}
			$pagination_nav .= '<li class="dot">...</li>';
		}
	}

	if( $page_num > 1 ) {
		$pagination_nav .= '<li class="' . ( $current == $page_num ? 'active' : '' ) . '"><a href="javascript:CityGuide.goPage(' . $page_num . ');">' . $page_num . '</a></li>';
	}
	$pagination_nav	.=	'
						<li class="next-nav ' . ($current == $page_num ? 'disabled' : '') . '">
							<a href="javascript:' . ($current == $page_num ? 'void(0)' : 'CityGuide.goPage(' . $current + 1 . ')') . ';">
								<i aria-hidden="true" class="fas fa-angle-right"></i>
							</a>
						</li>';

	?>
		<ul>
			<?php echo $pagination_nav; ?>
		</ul>
	<?php
}

function retrieve_url( $url ) {
	$site_url = preg_replace('/http.*:\/\//', '', $url);
	$site_url = preg_replace('/^www\./', '', $site_url);
	$site_url = preg_replace('/\/.*$/', '', $site_url);

	return $site_url;
}

function custom_wpcf7_form_class_attr( $class ){ 
	if( isset($_SESSION['user_email']) )
		$class .= ' display-signup-after-message';
    return $class;
}
add_filter('wpcf7_form_class_attr', 'custom_wpcf7_form_class_attr', 10, 1);

add_action( 'wpcf7_mail_sent', 'store_data' );
function store_data( $contact_form ) {
	$form_id = $contact_form->id();
	$submission = WPCF7_Submission::get_instance(); 
    $posted_data = $submission->get_posted_data();
	
	if( $form_id == 2724 ) { // email submission
		store_email_to_session( $posted_data['signup-email'] );
	} else if( $form_id == 2974 ) { // review submission
		store_review_to_db( $posted_data );
	}
}

function store_email_to_session($email) {
	$_SESSION['user_email'] = $email;
}

function store_review_to_db( $posted_data ) {
	global $wpdb;
	// update rating of establelishment.
	$wpdb->query($wpdb->prepare("
		UPDATE
			wp_stylebible_establelishments
		SET
			rating=rating+1
		WHERE
			establelishment_id=" . $posted_data['establelishment_id']));
	// insert review to db
	$store_data = [
		'establelishment_id'	=>	$posted_data['establelishment_id'],
		'review_content'		=>	$posted_data['review_content'],
		'customer_email'		=>	$_SESSION['user_email']
	];
	$wpdb->insert(
		'wp_stylebible_reviews',
		$store_data,
		[
			'%d',
			'%s',
			'%s'
		]
	);
}

add_action( 'wp_ajax_the_can_leave_review',			'the_can_leave_review' );
add_action( 'wp_ajax_nopriv_the_can_leave_review',	'the_can_leave_review' );
function the_can_leave_review() {
	global $wpdb;
	$review_id = $wpdb->get_var(
		'SELECT
			review_id
		FROM
			wp_stylebible_reviews
		WHERE
			establelishment_id=' . $_POST['establelishmentId'] . '
			and customer_email=\'' . $_SESSION['user_email'] . '\'');
	
	echo json_encode(['review_id' => $review_id]);
	wp_die();
}
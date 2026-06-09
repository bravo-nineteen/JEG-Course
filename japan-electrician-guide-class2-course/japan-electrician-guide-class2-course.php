<?php
/**
 * Plugin Name: Japan Electrician Guide Class 2 Course
 * Description: Structured English-language study course for the Japanese Class 2 Electrician written exam.
 * Version: 1.0.0
 * Author: GitHub Copilot
 * Text Domain: jeg-class2-course
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'JEG_Class2_Course' ) ) {

final class JEG_Class2_Course {
	const VERSION        = '1.0.0';
	const TEXT_DOMAIN    = 'jeg-class2-course';
	const CPT            = 'jeg_lesson';
	const TAXONOMY       = 'jeg_course_category';
	const META_PREFIX    = '_jeg_class2_';
	const OPTION_SEEDED  = 'jeg_class2_course_seeded';
	const OPTION_VOCAB   = 'jeg_class2_vocab_entries';
	const OPTION_QUIZ    = 'jeg_class2_quiz_questions';

	public static function init() : void {
		add_action( 'init', array( __CLASS__, 'register_content_types' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_lesson_meta' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function load_textdomain() : void {
		load_plugin_textdomain( self::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public static function register_assets() : void {
		wp_register_style(
			'jeg-class2-course-style',
			plugins_url( 'assets/css/style.css', __FILE__ ),
			array(),
			self::VERSION
		);

		wp_register_script(
			'jeg-class2-course-script',
			plugins_url( 'assets/js/script.js', __FILE__ ),
			array(),
			self::VERSION,
			true
		);
	}

	public static function register_content_types() : void {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'               => __( 'Lessons', self::TEXT_DOMAIN ),
					'singular_name'      => __( 'Lesson', self::TEXT_DOMAIN ),
					'add_new'            => __( 'Add New', self::TEXT_DOMAIN ),
					'add_new_item'       => __( 'Add New Lesson', self::TEXT_DOMAIN ),
					'edit_item'          => __( 'Edit Lesson', self::TEXT_DOMAIN ),
					'new_item'           => __( 'New Lesson', self::TEXT_DOMAIN ),
					'view_item'          => __( 'View Lesson', self::TEXT_DOMAIN ),
					'search_items'       => __( 'Search Lessons', self::TEXT_DOMAIN ),
					'not_found'          => __( 'No lessons found', self::TEXT_DOMAIN ),
					'not_found_in_trash' => __( 'No lessons found in Trash', self::TEXT_DOMAIN ),
					'menu_name'          => __( 'Lessons', self::TEXT_DOMAIN ),
				),
				'public'              => true,
				'show_in_rest'        => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'class-2-electrician-lessons' ),
				'menu_icon'           => 'dashicons-welcome-learn-more',
				'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'revisions' ),
				'show_in_menu'        => true,
				'publicly_queryable'  => true,
				'capability_type'     => 'post',
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			array( self::CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'Course Categories', self::TEXT_DOMAIN ),
					'singular_name' => __( 'Course Category', self::TEXT_DOMAIN ),
					'search_items'  => __( 'Search Categories', self::TEXT_DOMAIN ),
					'all_items'     => __( 'All Categories', self::TEXT_DOMAIN ),
					'edit_item'     => __( 'Edit Category', self::TEXT_DOMAIN ),
					'update_item'   => __( 'Update Category', self::TEXT_DOMAIN ),
					'add_new_item'  => __( 'Add New Category', self::TEXT_DOMAIN ),
					'new_item_name' => __( 'New Category Name', self::TEXT_DOMAIN ),
					'menu_name'     => __( 'Course Categories', self::TEXT_DOMAIN ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'class-2-course-category' ),
			)
		);

		register_post_meta(
			self::CPT,
			self::META_PREFIX . 'difficulty',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	public static function register_shortcodes() : void {
		add_shortcode( 'jeg_class2_course', array( __CLASS__, 'render_course_shortcode' ) );
		add_shortcode( 'jeg_class2_vocab', array( __CLASS__, 'render_vocab_shortcode' ) );
		add_shortcode( 'jeg_class2_quiz', array( __CLASS__, 'render_quiz_shortcode' ) );
	}

	public static function register_meta_boxes() : void {
		add_meta_box(
			'jeg_class2_lesson_details',
			__( 'Lesson Details', self::TEXT_DOMAIN ),
			array( __CLASS__, 'render_lesson_metabox' ),
			self::CPT,
			'normal',
			'high'
		);
	}

	public static function render_lesson_metabox( WP_Post $post ) : void {
		wp_nonce_field( 'jeg_class2_save_lesson', 'jeg_class2_lesson_nonce' );

		$difficulty        = get_post_meta( $post->ID, self::META_PREFIX . 'difficulty', true );
		$study_time        = get_post_meta( $post->ID, self::META_PREFIX . 'study_time', true );
		$key_vocabulary    = get_post_meta( $post->ID, self::META_PREFIX . 'key_vocabulary', true );
		$formula           = get_post_meta( $post->ID, self::META_PREFIX . 'formula', true );
		$worked_example    = get_post_meta( $post->ID, self::META_PREFIX . 'worked_example', true );
		$exam_tips         = get_post_meta( $post->ID, self::META_PREFIX . 'exam_tips', true );
		$practice_question  = get_post_meta( $post->ID, self::META_PREFIX . 'practice_question', true );
		$answer            = get_post_meta( $post->ID, self::META_PREFIX . 'answer', true );
		$explanation       = get_post_meta( $post->ID, self::META_PREFIX . 'answer_explanation', true );
		$simple_explanation = get_post_meta( $post->ID, self::META_PREFIX . 'simple_explanation', true );
		$detailed_explanation = get_post_meta( $post->ID, self::META_PREFIX . 'detailed_explanation', true );
		$common_trap       = get_post_meta( $post->ID, self::META_PREFIX . 'common_trap', true );
		$site_reality      = get_post_meta( $post->ID, self::META_PREFIX . 'site_reality', true );
		$lesson_term       = get_post_meta( $post->ID, self::META_PREFIX . 'japanese_key_term', true );
		$hiragana          = get_post_meta( $post->ID, self::META_PREFIX . 'hiragana', true );
		$english_meaning   = get_post_meta( $post->ID, self::META_PREFIX . 'english_meaning', true );
		?>
		<div class="jeg-admin-fields">
			<p>
				<label for="jeg_class2_difficulty"><strong><?php echo esc_html__( 'Difficulty', self::TEXT_DOMAIN ); ?></strong></label><br>
				<select id="jeg_class2_difficulty" name="jeg_class2_difficulty">
					<?php
					$difficulty_options = array(
						'Beginner'      => __( 'Beginner', self::TEXT_DOMAIN ),
						'Intermediate'  => __( 'Intermediate', self::TEXT_DOMAIN ),
						'Exam Level'    => __( 'Exam Level', self::TEXT_DOMAIN ),
					);
					foreach ( $difficulty_options as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $difficulty, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php
					endforeach;
					?>
				</select>
			</p>

			<p>
				<label for="jeg_class2_study_time"><strong><?php echo esc_html__( 'Estimated Study Time', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_study_time" name="jeg_class2_study_time" value="<?php echo esc_attr( $study_time ); ?>" placeholder="<?php echo esc_attr__( '30 minutes', self::TEXT_DOMAIN ); ?>">
			</p>

			<p>
				<label for="jeg_class2_japanese_key_term"><strong><?php echo esc_html__( 'Japanese Key Term', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_japanese_key_term" name="jeg_class2_japanese_key_term" value="<?php echo esc_attr( $lesson_term ); ?>" placeholder="電圧（でんあつ / voltage）">
			</p>

			<p>
				<label for="jeg_class2_hiragana"><strong><?php echo esc_html__( 'Hiragana', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_hiragana" name="jeg_class2_hiragana" value="<?php echo esc_attr( $hiragana ); ?>" placeholder="でんあつ">
			</p>

			<p>
				<label for="jeg_class2_english_meaning"><strong><?php echo esc_html__( 'English Meaning', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_english_meaning" name="jeg_class2_english_meaning" value="<?php echo esc_attr( $english_meaning ); ?>" placeholder="Voltage">
			</p>

			<p>
				<label for="jeg_class2_simple_explanation"><strong><?php echo esc_html__( 'Simple Explanation', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="4" id="jeg_class2_simple_explanation" name="jeg_class2_simple_explanation"><?php echo esc_textarea( $simple_explanation ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_detailed_explanation"><strong><?php echo esc_html__( 'Detailed Explanation', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="6" id="jeg_class2_detailed_explanation" name="jeg_class2_detailed_explanation"><?php echo esc_textarea( $detailed_explanation ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_key_vocabulary"><strong><?php echo esc_html__( 'Key Vocabulary', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="5" id="jeg_class2_key_vocabulary" name="jeg_class2_key_vocabulary"><?php echo esc_textarea( $key_vocabulary ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_formula"><strong><?php echo esc_html__( 'Formula', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="3" id="jeg_class2_formula" name="jeg_class2_formula"><?php echo esc_textarea( $formula ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_worked_example"><strong><?php echo esc_html__( 'Worked Example', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="4" id="jeg_class2_worked_example" name="jeg_class2_worked_example"><?php echo esc_textarea( $worked_example ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_exam_tips"><strong><?php echo esc_html__( 'Exam Tips', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="4" id="jeg_class2_exam_tips" name="jeg_class2_exam_tips"><?php echo esc_textarea( $exam_tips ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_common_trap"><strong><?php echo esc_html__( 'Common Exam Trap', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="3" id="jeg_class2_common_trap" name="jeg_class2_common_trap"><?php echo esc_textarea( $common_trap ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_site_reality"><strong><?php echo esc_html__( 'Site Reality in Japan', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="3" id="jeg_class2_site_reality" name="jeg_class2_site_reality"><?php echo esc_textarea( $site_reality ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_practice_question"><strong><?php echo esc_html__( 'Practice Question', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="4" id="jeg_class2_practice_question" name="jeg_class2_practice_question"><?php echo esc_textarea( $practice_question ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_answer"><strong><?php echo esc_html__( 'Answer', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="3" id="jeg_class2_answer" name="jeg_class2_answer"><?php echo esc_textarea( $answer ); ?></textarea>
			</p>

			<p>
				<label for="jeg_class2_answer_explanation"><strong><?php echo esc_html__( 'Answer Explanation', self::TEXT_DOMAIN ); ?></strong></label><br>
				<textarea class="widefat" rows="4" id="jeg_class2_answer_explanation" name="jeg_class2_answer_explanation"><?php echo esc_textarea( $explanation ); ?></textarea>
			</p>
		</div>
		<?php
	}

	public static function save_lesson_meta( int $post_id, WP_Post $post ) : void {
		if ( ! isset( $_POST['jeg_class2_lesson_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jeg_class2_lesson_nonce'] ) ), 'jeg_class2_save_lesson' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'difficulty'          => 'sanitize_text_field',
			'study_time'          => 'sanitize_text_field',
			'japanese_key_term'   => 'sanitize_text_field',
			'hiragana'            => 'sanitize_text_field',
			'english_meaning'     => 'sanitize_text_field',
			'simple_explanation'  => 'wp_kses_post',
			'detailed_explanation'=> 'wp_kses_post',
			'key_vocabulary'      => 'wp_kses_post',
			'formula'             => 'wp_kses_post',
			'worked_example'      => 'wp_kses_post',
			'exam_tips'           => 'wp_kses_post',
			'common_trap'         => 'wp_kses_post',
			'site_reality'        => 'wp_kses_post',
			'practice_question'   => 'wp_kses_post',
			'answer'              => 'wp_kses_post',
			'answer_explanation'  => 'wp_kses_post',
		);

		foreach ( $fields as $field => $sanitizer ) {
			$input_name = 'jeg_class2_' . $field;
			$value      = isset( $_POST[ $input_name ] ) ? wp_unslash( $_POST[ $input_name ] ) : '';

			if ( 'wp_kses_post' === $sanitizer ) {
				$value = wp_kses_post( $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			update_post_meta( $post_id, self::META_PREFIX . $field, $value );
		}
	}

	public static function register_admin_menu() : void {
		add_menu_page(
			__( 'JEG Class 2 Course', self::TEXT_DOMAIN ),
			__( 'JEG Class 2 Course', self::TEXT_DOMAIN ),
			'edit_posts',
			'jeg-class2-course',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-book-alt',
			26
		);
	}

	public static function render_admin_page() : void {
		$categories = self::default_course_categories();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'JEG Class 2 Course', self::TEXT_DOMAIN ); ?></h1>
			<p><?php echo esc_html__( 'Japan Electrician Guide Class 2 Course provides a structured English-language study path for the Japanese 第二種電気工事士 written exam.', self::TEXT_DOMAIN ); ?></p>

			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Plugin Overview', self::TEXT_DOMAIN ); ?></h2>
				<p><?php echo esc_html__( 'Use Lessons for structured study posts, Vocabulary for bilingual technical terms, and Quiz for quick exam practice.', self::TEXT_DOMAIN ); ?></p>
				<p><?php echo esc_html__( 'The front end uses a dark industrial layout with white study cards so the content feels technical but still easy to read on mobile.', self::TEXT_DOMAIN ); ?></p>
			</div>

			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Shortcode Instructions', self::TEXT_DOMAIN ); ?></h2>
				<p><strong>[jeg_class2_course]</strong> <?php echo esc_html__( 'shows the course homepage, filters, lesson cards, and the selected lesson detail.', self::TEXT_DOMAIN ); ?></p>
				<p><strong>[jeg_class2_vocab]</strong> <?php echo esc_html__( 'shows the searchable bilingual vocabulary table.', self::TEXT_DOMAIN ); ?></p>
				<p><strong>[jeg_class2_quiz]</strong> <?php echo esc_html__( 'shows the interactive multiple-choice quiz with score tracking and explanations.', self::TEXT_DOMAIN ); ?></p>
			</div>

			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Course Categories', self::TEXT_DOMAIN ); ?></h2>
				<ul style="columns: 2; margin-left: 1.25rem;">
					<?php foreach ( $categories as $category ) : ?>
						<li><?php echo esc_html( $category['name'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'How to Add Lessons', self::TEXT_DOMAIN ); ?></h2>
				<ol style="margin-left: 1.25rem;">
					<li><?php echo esc_html__( 'Open Lessons in the dashboard and add a new lesson.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Set a title, add the lesson body, and choose one or more course categories.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Fill in the lesson details box with difficulty, study time, vocabulary, formulas, examples, and exam guidance.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Publish the lesson, then place the course shortcode on a page.', self::TEXT_DOMAIN ); ?></li>
				</ol>
			</div>

			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Vocabulary Display', self::TEXT_DOMAIN ); ?></h2>
				<p><?php echo esc_html__( 'The vocabulary shortcode reads the starter database from plugin options. You can later swap it to custom post types or a custom table without changing the front-end shortcode.', self::TEXT_DOMAIN ); ?></p>
			</div>

			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Quiz Display', self::TEXT_DOMAIN ); ?></h2>
				<p><?php echo esc_html__( 'Quiz questions are currently stored in a structured array and rendered interactively. This keeps the system simple now and easy to move into the database later.', self::TEXT_DOMAIN ); ?></p>
			</div>

			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Future Expansion Notes', self::TEXT_DOMAIN ); ?></h2>
				<ul style="margin-left: 1.25rem;">
					<li><?php echo esc_html__( 'Add spaced-repetition tracking and user progress storage.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Move vocabulary and quiz content into custom database tables or REST-backed content types.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Add diagram uploads, printable worksheets, and exam mode timers.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Add multilingual support for Chinese, Korean, or Portuguese learners later.', self::TEXT_DOMAIN ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	public static function render_course_shortcode( $atts = array() ) : string {
		self::enqueue_frontend_assets();

		$lessons    = self::get_lessons();
		$categories  = self::default_course_categories();
		$current_id  = isset( $_GET['jeg_lesson'] ) ? absint( wp_unslash( $_GET['jeg_lesson'] ) ) : 0;
		$base_url    = self::get_current_url();
		$selected    = $current_id ? get_post( $current_id ) : null;
		$output      = array();
		$course_name = esc_html__( 'Japan Electrician Guide - Class 2 Electrician Course', self::TEXT_DOMAIN );

		ob_start();
		?>
		<section class="jeg-course-shell" data-jeg-course>
			<div class="jeg-course-hero">
				<p class="jeg-eyebrow"><?php echo esc_html__( 'English study course for the Japanese written exam', self::TEXT_DOMAIN ); ?></p>
				<h2><?php echo esc_html( $course_name ); ?></h2>
				<p class="jeg-lead"><?php echo esc_html__( 'Study electrical theory, Japanese technical vocabulary, calculations, wiring rules, safety, tools, and exam-style questions in one structured path.', self::TEXT_DOMAIN ); ?></p>
				<div class="jeg-progress-steps" aria-label="<?php echo esc_attr__( 'Study path', self::TEXT_DOMAIN ); ?>">
					<div class="jeg-step"><span>1</span><strong><?php echo esc_html__( 'Learn the concept', self::TEXT_DOMAIN ); ?></strong></div>
					<div class="jeg-step"><span>2</span><strong><?php echo esc_html__( 'Practice calculations', self::TEXT_DOMAIN ); ?></strong></div>
					<div class="jeg-step"><span>3</span><strong><?php echo esc_html__( 'Check exam traps', self::TEXT_DOMAIN ); ?></strong></div>
				</div>
			</div>

			<div class="jeg-course-toolbar">
				<div class="jeg-search-field">
					<label class="screen-reader-text" for="jeg-course-search"><?php echo esc_html__( 'Search lessons', self::TEXT_DOMAIN ); ?></label>
					<input type="search" id="jeg-course-search" class="jeg-search-input" placeholder="<?php echo esc_attr__( 'Search lessons, vocabulary, formulas, and exam topics', self::TEXT_DOMAIN ); ?>" data-jeg-course-search>
				</div>
				<div class="jeg-filter-row" data-jeg-course-filters>
					<button type="button" class="jeg-filter-button is-active" data-filter="all"><?php echo esc_html__( 'All Lessons', self::TEXT_DOMAIN ); ?></button>
					<?php foreach ( $categories as $category ) : ?>
						<button type="button" class="jeg-filter-button" data-filter="<?php echo esc_attr( $category['slug'] ); ?>"><?php echo esc_html( $category['name'] ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="jeg-course-summary">
				<div class="jeg-summary-card">
					<strong><?php echo esc_html( number_format_i18n( count( $lessons ) ) ); ?></strong>
					<span><?php echo esc_html__( 'Starter lessons', self::TEXT_DOMAIN ); ?></span>
				</div>
				<div class="jeg-summary-card">
					<strong><?php echo esc_html( number_format_i18n( count( self::get_vocab_entries() ) ) ); ?></strong>
					<span><?php echo esc_html__( 'Vocabulary terms', self::TEXT_DOMAIN ); ?></span>
				</div>
				<div class="jeg-summary-card">
					<strong><?php echo esc_html__( 'Beginner friendly', self::TEXT_DOMAIN ); ?></strong>
					<span><?php echo esc_html__( 'Clear English explanations', self::TEXT_DOMAIN ); ?></span>
				</div>
			</div>

			<div class="jeg-lesson-grid">
				<?php foreach ( $lessons as $lesson ) :
					$lesson_terms = wp_get_post_terms( $lesson->ID, self::TAXONOMY, array( 'fields' => 'slugs' ) );
					$categories_for_card = is_array( $lesson_terms ) ? implode( ' ', $lesson_terms ) : '';
					$excerpt = wp_trim_words( wp_strip_all_tags( $lesson->post_excerpt ? $lesson->post_excerpt : $lesson->post_content ), 28, '...' );
					$detail_url = add_query_arg( 'jeg_lesson', $lesson->ID, $base_url );
					$lesson_difficulty = get_post_meta( $lesson->ID, self::META_PREFIX . 'difficulty', true );
					$study_time = get_post_meta( $lesson->ID, self::META_PREFIX . 'study_time', true );
					?>
					<article class="jeg-lesson-card <?php echo $current_id === (int) $lesson->ID ? 'is-active' : ''; ?>" data-course-card data-category="<?php echo esc_attr( $categories_for_card ); ?>" data-title="<?php echo esc_attr( strtolower( $lesson->post_title ) ); ?>" data-excerpt="<?php echo esc_attr( strtolower( $excerpt ) ); ?>">
						<div class="jeg-card-meta-row">
							<span class="jeg-card-tag"><?php echo esc_html( $lesson_difficulty ? $lesson_difficulty : __( 'Beginner', self::TEXT_DOMAIN ) ); ?></span>
							<span class="jeg-card-time"><?php echo esc_html( $study_time ? $study_time : __( '30 minutes', self::TEXT_DOMAIN ) ); ?></span>
						</div>
						<h3><?php echo esc_html( $lesson->post_title ); ?></h3>
						<div class="jeg-card-categories">
							<?php foreach ( $lesson_terms as $lesson_term_slug ) :
								$term = get_term_by( 'slug', $lesson_term_slug, self::TAXONOMY );
								if ( $term && ! is_wp_error( $term ) ) :
									?>
									<span><?php echo esc_html( $term->name ); ?></span>
									<?php
								endif;
							endforeach;
							?>
						</div>
						<p><?php echo esc_html( $excerpt ); ?></p>
						<a class="jeg-button" href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html__( 'Start Lesson', self::TEXT_DOMAIN ); ?></a>
					</article>
				<?php endforeach; ?>
			</div>

			<?php if ( $selected instanceof WP_Post && self::CPT === $selected->post_type ) : ?>
				<div class="jeg-selected-lesson" id="jeg-selected-lesson">
					<?php echo wp_kses_post( self::render_lesson_detail( $selected ) ); ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_vocab_shortcode( $atts = array() ) : string {
		self::enqueue_frontend_assets();
		$entries = self::get_vocab_entries();

		ob_start();
		?>
		<section class="jeg-vocab-shell" data-jeg-vocab>
			<div class="jeg-course-hero jeg-course-hero--compact">
				<p class="jeg-eyebrow"><?php echo esc_html__( 'Bilingual technical vocabulary', self::TEXT_DOMAIN ); ?></p>
				<h2><?php echo esc_html__( 'Class 2 Electrician Vocabulary', self::TEXT_DOMAIN ); ?></h2>
				<p class="jeg-lead"><?php echo esc_html__( 'Search Japanese terms, readings, English meanings, and exam relevance.', self::TEXT_DOMAIN ); ?></p>
			</div>

			<div class="jeg-vocab-search">
				<label class="screen-reader-text" for="jeg-vocab-search-input"><?php echo esc_html__( 'Search vocabulary', self::TEXT_DOMAIN ); ?></label>
				<input type="search" id="jeg-vocab-search-input" class="jeg-search-input" placeholder="<?php echo esc_attr__( 'Search 電圧, grounding, breaker, connector...', self::TEXT_DOMAIN ); ?>" data-jeg-vocab-search>
			</div>

			<div class="jeg-table-wrap">
				<table class="jeg-vocab-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Japanese', self::TEXT_DOMAIN ); ?></th>
							<th><?php echo esc_html__( 'Hiragana / Reading', self::TEXT_DOMAIN ); ?></th>
							<th><?php echo esc_html__( 'English', self::TEXT_DOMAIN ); ?></th>
							<th><?php echo esc_html__( 'Explanation', self::TEXT_DOMAIN ); ?></th>
							<th><?php echo esc_html__( 'Example', self::TEXT_DOMAIN ); ?></th>
							<th><?php echo esc_html__( 'Exam Point', self::TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $entry ) :
							$search_text = strtolower( implode( ' ', $entry ) );
							?>
							<tr data-vocab-row data-search="<?php echo esc_attr( $search_text ); ?>">
								<td><strong><?php echo esc_html( $entry['Japanese'] ); ?></strong></td>
								<td><?php echo esc_html( $entry['Hiragana'] ); ?></td>
								<td><?php echo esc_html( $entry['English'] ); ?></td>
								<td><?php echo esc_html( $entry['Explanation'] ); ?></td>
								<td><?php echo esc_html( $entry['Example'] ); ?></td>
								<td><?php echo esc_html( $entry['Exam Point'] ); ?></td>
							</tr>
							<?php
						endforeach;
						?>
					</tbody>
				</table>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_quiz_shortcode( $atts = array() ) : string {
		self::enqueue_frontend_assets();
		$questions = self::get_quiz_questions();

		ob_start();
		?>
		<section class="jeg-quiz-shell" data-jeg-quiz>
			<div class="jeg-course-hero jeg-course-hero--compact">
				<p class="jeg-eyebrow"><?php echo esc_html__( 'Quick exam practice', self::TEXT_DOMAIN ); ?></p>
				<h2><?php echo esc_html__( 'Class 2 Electrician Quiz', self::TEXT_DOMAIN ); ?></h2>
				<p class="jeg-lead"><?php echo esc_html__( 'Answer one question at a time, review the explanation, and watch your score update.', self::TEXT_DOMAIN ); ?></p>
			</div>

			<div class="jeg-quiz-scoreboard" data-jeg-quiz-score>
				<span><?php echo esc_html__( 'Score', self::TEXT_DOMAIN ); ?></span>
				<strong>0 / <?php echo esc_html( number_format_i18n( count( $questions ) ) ); ?></strong>
			</div>

			<div class="jeg-quiz-list" data-jeg-quiz-list>
				<?php foreach ( $questions as $index => $question ) : ?>
					<div class="jeg-quiz-question" data-question data-correct="<?php echo esc_attr( (string) $question['correct'] ); ?>">
						<h3><?php echo esc_html( ( $index + 1 ) . '. ' . $question['question'] ); ?></h3>
						<div class="jeg-quiz-choices">
							<?php foreach ( $question['choices'] as $choice_index => $choice ) : ?>
								<button type="button" class="jeg-quiz-choice" data-choice-index="<?php echo esc_attr( (string) $choice_index ); ?>"><?php echo esc_html( $choice ); ?></button>
							<?php endforeach; ?>
						</div>
						<div class="jeg-quiz-feedback" aria-live="polite">
							<p class="jeg-quiz-answer is-hidden" data-quiz-answer></p>
							<p class="jeg-quiz-explanation is-hidden" data-quiz-explanation><?php echo esc_html( $question['explanation'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="jeg-quiz-actions">
				<button type="button" class="jeg-button jeg-button--secondary" data-jeg-quiz-restart><?php echo esc_html__( 'Restart Quiz', self::TEXT_DOMAIN ); ?></button>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function enqueue_frontend_assets() : void {
		wp_enqueue_style( 'jeg-class2-course-style' );
		wp_enqueue_script( 'jeg-class2-course-script' );
	}

	public static function activate() : void {
		self::register_content_types();
		self::seed_default_content();
		flush_rewrite_rules();
	}

	public static function deactivate() : void {
		flush_rewrite_rules();
	}

	private static function seed_default_content() : void {
		if ( get_option( self::OPTION_SEEDED ) ) {
			return;
		}

		foreach ( self::default_course_categories() as $category ) {
			if ( ! term_exists( $category['slug'], self::TAXONOMY ) ) {
				wp_insert_term( $category['name'], self::TAXONOMY, array( 'slug' => $category['slug'] ) );
			}
		}

		update_option( self::OPTION_VOCAB, self::default_vocab_entries() );
		update_option( self::OPTION_QUIZ, self::default_quiz_questions() );

		$existing = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			update_option( self::OPTION_SEEDED, 1 );
			return;
		}

		$lesson_map = self::default_lessons();

		foreach ( $lesson_map as $lesson ) {
			$lesson_id = wp_insert_post(
				array(
					'post_type'    => self::CPT,
					'post_status'  => 'publish',
					'post_title'   => $lesson['title'],
					'post_content' => $lesson['content'],
					'post_excerpt' => $lesson['excerpt'],
					'menu_order'   => (int) $lesson['order'],
				)
			);

			if ( is_wp_error( $lesson_id ) || ! $lesson_id ) {
				continue;
			}

			wp_set_object_terms( $lesson_id, $lesson['categories'], self::TAXONOMY );

			update_post_meta( $lesson_id, self::META_PREFIX . 'difficulty', $lesson['difficulty'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'study_time', $lesson['study_time'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'japanese_key_term', $lesson['japanese_key_term'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'hiragana', $lesson['hiragana'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'english_meaning', $lesson['english_meaning'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'simple_explanation', $lesson['simple_explanation'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'detailed_explanation', $lesson['detailed_explanation'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'key_vocabulary', $lesson['key_vocabulary'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'formula', $lesson['formula'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'worked_example', $lesson['worked_example'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'exam_tips', $lesson['exam_tips'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'common_trap', $lesson['common_trap'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'site_reality', $lesson['site_reality'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'practice_question', $lesson['practice_question'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'answer', $lesson['answer'] );
			update_post_meta( $lesson_id, self::META_PREFIX . 'answer_explanation', $lesson['answer_explanation'] );
		}

		update_option( self::OPTION_SEEDED, 1 );
	}

	private static function get_lessons() : array {
		return get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'order'          => 'ASC',
			)
		);
	}

	private static function render_lesson_detail( WP_Post $lesson ) : string {
		$meta = array(
			'Japanese Key Term'    => get_post_meta( $lesson->ID, self::META_PREFIX . 'japanese_key_term', true ),
			'Hiragana'             => get_post_meta( $lesson->ID, self::META_PREFIX . 'hiragana', true ),
			'English Meaning'      => get_post_meta( $lesson->ID, self::META_PREFIX . 'english_meaning', true ),
			'Simple Explanation'   => get_post_meta( $lesson->ID, self::META_PREFIX . 'simple_explanation', true ),
			'Detailed Explanation'  => get_post_meta( $lesson->ID, self::META_PREFIX . 'detailed_explanation', true ),
			'Key Vocabulary'       => get_post_meta( $lesson->ID, self::META_PREFIX . 'key_vocabulary', true ),
			'Formula'              => get_post_meta( $lesson->ID, self::META_PREFIX . 'formula', true ),
			'Worked Example'       => get_post_meta( $lesson->ID, self::META_PREFIX . 'worked_example', true ),
			'Exam Tips'            => get_post_meta( $lesson->ID, self::META_PREFIX . 'exam_tips', true ),
			'Common Exam Trap'     => get_post_meta( $lesson->ID, self::META_PREFIX . 'common_trap', true ),
			'Site Reality in Japan' => get_post_meta( $lesson->ID, self::META_PREFIX . 'site_reality', true ),
			'Practice Question'    => get_post_meta( $lesson->ID, self::META_PREFIX . 'practice_question', true ),
			'Answer'               => get_post_meta( $lesson->ID, self::META_PREFIX . 'answer', true ),
			'Explanation'          => get_post_meta( $lesson->ID, self::META_PREFIX . 'answer_explanation', true ),
		);

		$categories = get_the_terms( $lesson, self::TAXONOMY );
		ob_start();
		?>
		<article class="jeg-lesson-detail">
			<div class="jeg-lesson-detail__header">
				<p class="jeg-eyebrow"><?php echo esc_html__( 'Selected lesson', self::TEXT_DOMAIN ); ?></p>
				<h2><?php echo esc_html( $lesson->post_title ); ?></h2>
				<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
					<p class="jeg-detail-categories">
						<?php foreach ( $categories as $category ) : ?>
							<span><?php echo esc_html( $category->name ); ?></span>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="jeg-detail-body">
				<?php foreach ( $meta as $label => $value ) : ?>
					<?php if ( '' === trim( (string) $value ) ) : continue; endif; ?>
					<section class="jeg-detail-section">
						<h3><?php echo esc_html( $label ); ?></h3>
						<div class="jeg-rich-text"><?php echo wp_kses_post( wpautop( $value ) ); ?></div>
					</section>
				<?php endforeach; ?>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	private static function get_current_url() : string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$base        = home_url( $request_uri );

		return remove_query_arg( 'jeg_lesson', $base );
	}

	private static function default_course_categories() : array {
		return array(
			array( 'name' => 'Electrical Theory', 'slug' => 'electrical-theory' ),
			array( 'name' => 'Electrical Calculations', 'slug' => 'electrical-calculations' ),
			array( 'name' => 'Wiring Methods', 'slug' => 'wiring-methods' ),
			array( 'name' => 'Tools and Materials', 'slug' => 'tools-and-materials' ),
			array( 'name' => 'Safety and Regulations', 'slug' => 'safety-and-regulations' ),
			array( 'name' => 'Japanese Vocabulary', 'slug' => 'japanese-vocabulary' ),
			array( 'name' => 'Symbols and Diagrams', 'slug' => 'symbols-and-diagrams' ),
			array( 'name' => 'Inspection and Testing', 'slug' => 'inspection-and-testing' ),
			array( 'name' => 'Exam Practice', 'slug' => 'exam-practice' ),
		);
	}

	private static function default_vocab_entries() : array {
		return array(
			array(
				'Japanese'    => '電圧',
				'Hiragana'    => 'でんあつ',
				'English'     => 'Voltage',
				'Explanation' => 'The electrical pressure that pushes current through a circuit.',
				'Example'     => '電圧が高いほど、同じ抵抗では電流が大きくなる。',
				'Exam Point'  => 'Appears in Ohm\'s Law and power calculation questions.',
			),
			array(
				'Japanese'    => '電流',
				'Hiragana'    => 'でんりゅう',
				'English'     => 'Current',
				'Explanation' => 'The flow of electric charge in amperes.',
				'Example'     => '電流は回路を流れる量を表す。',
				'Exam Point'  => 'Commonly used with voltage and resistance.',
			),
			array(
				'Japanese'    => '抵抗',
				'Hiragana'    => 'ていこう',
				'English'     => 'Resistance',
				'Explanation' => 'The opposition to current flow, measured in ohms.',
				'Example'     => '抵抗が大きいと電流は小さくなる。',
				'Exam Point'  => 'Used in series/parallel formulas and Ohm\'s Law.',
			),
			array(
				'Japanese'    => '電力',
				'Hiragana'    => 'でんりょく',
				'English'     => 'Power',
				'Explanation' => 'The rate of electrical energy use, measured in watts.',
				'Example'     => '電力 = 電圧 × 電流。',
				'Exam Point'  => 'Often tested with appliance consumption.',
			),
			array(
				'Japanese'    => '交流',
				'Hiragana'    => 'こうりゅう',
				'English'     => 'Alternating Current',
				'Explanation' => 'Current that periodically changes direction.',
				'Example'     => '日本の住宅配線は交流を使う。',
				'Exam Point'  => 'Frequency questions often use AC.',
			),
			array(
				'Japanese'    => '直流',
				'Hiragana'    => 'ちょくりゅう',
				'English'     => 'Direct Current',
				'Explanation' => 'Current that flows in one direction.',
				'Example'     => '乾電池は直流の電源である。',
				'Exam Point'  => 'Compare AC and DC clearly.',
			),
			array(
				'Japanese'    => '接地',
				'Hiragana'    => 'せっち',
				'English'     => 'Grounding / Earthing',
				'Explanation' => 'Connecting equipment to earth for safety.',
				'Example'     => '金属製器具は接地で感電リスクを下げる。',
				'Exam Point'  => 'Very common safety and code question.',
			),
			array(
				'Japanese'    => '漏電',
				'Hiragana'    => 'ろうでん',
				'English'     => 'Electrical leakage',
				'Explanation' => 'Unwanted current flowing to grounded metal or earth.',
				'Example'     => '漏電があると遮断器が動作することがある。',
				'Exam Point'  => 'Linked to insulation resistance and ELCB questions.',
			),
			array(
				'Japanese'    => '短絡',
				'Hiragana'    => 'たんらく',
				'English'     => 'Short circuit',
				'Explanation' => 'A low-resistance accidental connection that causes a large current.',
				'Example'     => '短絡すると配線用遮断器が動作する。',
				'Exam Point'  => 'Usually asked with breaker protection.',
			),
			array(
				'Japanese'    => '絶縁抵抗',
				'Hiragana'    => 'ぜつえんていこう',
				'English'     => 'Insulation resistance',
				'Explanation' => 'The resistance of insulation material against leakage current.',
				'Example'     => '絶縁抵抗試験で配線の劣化を確認する。',
				'Exam Point'  => 'A frequent written exam testing topic.',
			),
			array(
				'Japanese'    => '配線用遮断器',
				'Hiragana'    => 'はいせんようしゃだんき',
				'English'     => 'Molded case circuit breaker',
				'Explanation' => 'A breaker that protects circuits from overload and short circuit.',
				'Example'     => '配線用遮断器は過電流保護に使う。',
				'Exam Point'  => 'Know the difference from ELCB.',
			),
			array(
				'Japanese'    => '漏電遮断器',
				'Hiragana'    => 'ろうでんしゃだんき',
				'English'     => 'Earth leakage circuit breaker',
				'Explanation' => 'A breaker that trips when leakage current is detected.',
				'Example'     => '漏電遮断器は感電防止に役立つ。',
				'Exam Point'  => 'Often appears in safety and grounding questions.',
			),
			array(
				'Japanese'    => '接続箱',
				'Hiragana'    => 'せつぞくばこ',
				'English'     => 'Junction box',
				'Explanation' => 'A box used to join or branch conductors safely.',
				'Example'     => '接続箱の中で電線を整理して接続する。',
				'Exam Point'  => 'Common in wiring method and diagram questions.',
			),
			array(
				'Japanese'    => '複線図',
				'Hiragana'    => 'ふくせんず',
				'English'     => 'Multi-line wiring diagram',
				'Explanation' => 'A diagram that shows each conductor separately.',
				'Example'     => '複線図でスイッチと照明の接続を確認する。',
				'Exam Point'  => 'Helpful for wiring question translation.',
			),
			array(
				'Japanese'    => '単線図',
				'Hiragana'    => 'たんせんず',
				'English'     => 'Single-line diagram',
				'Explanation' => 'A simplified wiring diagram using one line per circuit path.',
				'Example'     => '単線図は全体の構成を短く表す。',
				'Exam Point'  => 'A standard symbol interpretation question.',
			),
			array(
				'Japanese'    => 'リングスリーブ',
				'Hiragana'    => 'りんぐすりーぶ',
				'English'     => 'Ring sleeve',
				'Explanation' => 'A sleeve used for crimping and joining conductors.',
				'Example'     => 'リングスリーブは電線サイズに合わせて選ぶ。',
				'Exam Point'  => 'Selection and crimping are frequently tested.',
			),
			array(
				'Japanese'    => '差込形コネクタ',
				'Hiragana'    => 'さしこみがたこねくた',
				'English'     => 'Push-in connector',
				'Explanation' => 'A connector that joins wires by inserting them into a terminal body.',
				'Example'     => '差込形コネクタは現場で素早く接続できる。',
				'Exam Point'  => 'Use questions often ask when it is suitable.',
			),
			array(
				'Japanese'    => 'VVFケーブル',
				'Hiragana'    => 'ぶいぶいえふけーぶる',
				'English'     => 'VVF cable',
				'Explanation' => 'A standard flat vinyl-sheathed cable used in Japanese house wiring.',
				'Example'     => 'VVFケーブルは住宅配線でよく使う。',
				'Exam Point'  => 'Cable identification is a common exam topic.',
			),
			array(
				'Japanese'    => 'アウトレットボックス',
				'Hiragana'    => 'あうとれっとぼっくす',
				'English'     => 'Outlet box',
				'Explanation' => 'A box that supports wiring devices and protects connections.',
				'Example'     => 'アウトレットボックスに器具を固定する。',
				'Exam Point'  => 'Often tested with device installation diagrams.',
			),
			array(
				'Japanese'    => '金属管',
				'Hiragana'    => 'きんぞくかん',
				'English'     => 'Metal conduit',
				'Explanation' => 'A metal tube used to route and protect wiring.',
				'Example'     => '金属管は機械的保護に役立つ。',
				'Exam Point'  => 'Conduit questions appear in wiring and materials sections.',
			),
		);
	}

	private static function default_quiz_questions() : array {
		return array(
			array(
				'question'   => 'What is the formula for Ohm\'s Law?',
				'choices'    => array( 'V = IR', 'P = VI', 'I = PR', 'R = VP' ),
				'correct'    => 0,
				'explanation'=> 'Ohm\'s Law links voltage, current, and resistance as V = I × R.',
			),
			array(
				'question'   => 'Which formula calculates electrical power?',
				'choices'    => array( 'V = IR', 'P = VI', 'R = I/V', 'I = V/R' ),
				'correct'    => 1,
				'explanation'=> 'Power is voltage multiplied by current: P = V × I.',
			),
			array(
				'question'   => 'What usually happens to total resistance in a series circuit?',
				'choices'    => array( 'It decreases', 'It is the same as the smallest resistor', 'It increases', 'It becomes zero' ),
				'correct'    => 2,
				'explanation'=> 'In series, resistances add together, so total resistance increases.',
			),
			array(
				'question'   => 'Why is grounding important?',
				'choices'    => array( 'To increase power', 'To reduce voltage frequency', 'To provide a safe path for fault current', 'To make AC become DC' ),
				'correct'    => 2,
				'explanation'=> 'Grounding helps protect people and equipment by directing fault current safely.',
			),
			array(
				'question'   => 'A short circuit is usually caused by what?',
				'choices'    => array( 'High insulation resistance', 'An unintended low-resistance path', 'A low current load', 'A correct grounding connection' ),
				'correct'    => 1,
				'explanation'=> 'A short circuit creates a very low-resistance path and large current flow.',
			),
			array(
				'question'   => 'What does insulation resistance testing check?',
				'choices'    => array( 'Wire color', 'Leakage through insulation', 'Breaker brand', 'Voltage frequency' ),
				'correct'    => 1,
				'explanation'=> 'The test checks whether the insulation is preventing leakage current.',
			),
			array(
				'question'   => 'What is the main function of a breaker?',
				'choices'    => array( 'Generate voltage', 'Reduce wire size', 'Interrupt abnormal current', 'Change AC to DC' ),
				'correct'    => 2,
				'explanation'=> 'Breakers protect circuits by stopping overload or fault current.',
			),
			array(
				'question'   => 'When selecting a ring sleeve, what matters most?',
				'choices'    => array( 'Only the color', 'Wire size and connection method', 'The wall paint', 'The room temperature' ),
				'correct'    => 1,
				'explanation'=> 'Ring sleeve selection depends on conductor size and the required crimping method.',
			),
			array(
				'question'   => 'What does a single-line diagram usually show?',
				'choices'    => array( 'One wire only', 'A simplified circuit layout', 'Only the breaker label', 'The insulation test result' ),
				'correct'    => 1,
				'explanation'=> 'A single-line diagram simplifies the circuit into a compact system view.',
			),
			array(
				'question'   => 'Which Japanese term means voltage?',
				'choices'    => array( '電流', '抵抗', '電圧', '接地' ),
				'correct'    => 2,
				'explanation'=> '電圧 is read でんあつ and means voltage.',
			),
		);
	}

	private static function get_vocab_entries() : array {
		$entries = get_option( self::OPTION_VOCAB, array() );

		if ( ! is_array( $entries ) || empty( $entries ) ) {
			$entries = self::default_vocab_entries();
		}

		return array_map(
			static function ( $entry ) {
				return array(
					'Japanese'    => isset( $entry['Japanese'] ) ? (string) $entry['Japanese'] : '',
					'Hiragana'    => isset( $entry['Hiragana'] ) ? (string) $entry['Hiragana'] : '',
					'English'     => isset( $entry['English'] ) ? (string) $entry['English'] : '',
					'Explanation' => isset( $entry['Explanation'] ) ? (string) $entry['Explanation'] : '',
					'Example'     => isset( $entry['Example'] ) ? (string) $entry['Example'] : '',
					'Exam Point'  => isset( $entry['Exam Point'] ) ? (string) $entry['Exam Point'] : '',
				);
			},
			$entries
		);
	}

	private static function get_quiz_questions() : array {
		$questions = get_option( self::OPTION_QUIZ, array() );

		if ( ! is_array( $questions ) || empty( $questions ) ) {
			$questions = self::default_quiz_questions();
		}

		return array_map(
			static function ( $question ) {
				return array(
					'question'    => isset( $question['question'] ) ? (string) $question['question'] : '',
					'choices'     => isset( $question['choices'] ) && is_array( $question['choices'] ) ? array_values( $question['choices'] ) : array(),
					'correct'     => isset( $question['correct'] ) ? absint( $question['correct'] ) : 0,
					'explanation' => isset( $question['explanation'] ) ? (string) $question['explanation'] : '',
				);
			},
			$questions
		);
	}

	private static function default_lessons() : array {
		return array(
			array(
				'order'                => 1,
				'title'                => 'Lesson 1: Electrical Theory Essentials',
				'excerpt'              => 'Start with voltage, current, resistance, and the basic physical meaning of an electrical circuit.',
				'categories'           => array( 'electrical-theory', 'japanese-vocabulary' ),
				'difficulty'           => 'Beginner',
				'study_time'           => '35 minutes',
				'japanese_key_term'    => '電圧（でんあつ / voltage）',
				'hiragana'             => 'でんあつ',
				'english_meaning'      => 'Voltage',
				'simple_explanation'   => 'Voltage is the force that pushes current through a circuit. If you understand voltage, current, and resistance together, most beginner exam questions become easier.',
				'detailed_explanation' => 'The written exam often asks you to interpret the meaning of voltage, current, and resistance in practical wiring situations. In Japanese construction language, you will often see 電圧（でんあつ / voltage）, 電流（でんりゅう / current）, and 抵抗（ていこう / resistance） in the same question. Learn the English meaning and the Japanese reading together.',
				'key_vocabulary'       => "- 電圧（でんあつ / voltage）\n- 電流（でんりゅう / current）\n- 抵抗（ていこう / resistance）\n- 電力（でんりょく / power）",
				'formula'              => 'V = I × R',
				'worked_example'       => 'If current is 2 A and resistance is 6 Ω, voltage is 2 × 6 = 12 V.',
				'exam_tips'            => 'When the problem uses voltage, current, and resistance together, write the formula first before inserting numbers.',
				'common_trap'          => 'Do not confuse voltage with current. Voltage is the push; current is the flow.',
				'site_reality'         => 'On site, electricians often talk about whether a circuit is live, measured, or isolated. The written exam checks whether you understand the meaning behind the numbers.',
				'practice_question'    => 'A circuit has 10 V and 5 Ω. What is the current?',
				'answer'               => '2 A',
				'answer_explanation'   => 'Use I = V / R. 10 / 5 = 2 A.',
				'content'              => '<p>This lesson builds the base for every later topic. Learn the core words first, then connect them to simple calculations and real wiring situations.</p>',
			),
			array(
				'order'                => 2,
				'title'                => 'Lesson 2: Ohm\'s Law and Power',
				'excerpt'              => 'Use V = IR and P = VI to solve the most common written exam calculation questions.',
				'categories'           => array( 'electrical-calculations', 'electrical-theory', 'exam-practice' ),
				'difficulty'           => 'Beginner',
				'study_time'           => '40 minutes',
				'japanese_key_term'    => '電力（でんりょく / power）',
				'hiragana'             => 'でんりょく',
				'english_meaning'      => 'Power',
				'simple_explanation'   => 'Ohm\'s Law connects voltage, current, and resistance. Power tells you how fast electrical energy is being used.',
				'detailed_explanation' => 'The exam often combines one formula with a short practical story. You may be asked to calculate current from voltage and resistance, then immediately calculate power from the answer. Take it in steps and keep the units visible.',
				'key_vocabulary'       => "- 電圧（でんあつ / voltage）\n- 電流（でんりゅう / current）\n- 抵抗（ていこう / resistance）\n- 電力（でんりょく / power）",
				'formula'              => 'V = I × R\nP = V × I',
				'worked_example'       => 'If V = 100 V and I = 0.5 A, then P = 100 × 0.5 = 50 W.',
				'exam_tips'            => 'Write the unit next to every answer. Many exam mistakes are unit mistakes, not math mistakes.',
				'common_trap'          => 'Do not use kilowatts and watts without converting. 1 kW = 1000 W.',
				'site_reality'         => 'In real electrical work, power information helps you match equipment ratings and breaker capacity.',
				'practice_question'    => 'A heater uses 2 A at 200 V. What is its power?',
				'answer'               => '400 W',
				'answer_explanation'   => 'Use P = V × I. 200 × 2 = 400 W.',
				'content'              => '<p>This lesson is the main bridge between theory and exam calculations. Keep the formulas short and practice them until they are automatic.</p>',
			),
			array(
				'order'                => 3,
				'title'                => 'Lesson 3: Series and Parallel Circuits',
				'excerpt'              => 'Learn how resistance and current behave differently in series and parallel circuits.',
				'categories'           => array( 'electrical-theory', 'electrical-calculations' ),
				'difficulty'           => 'Beginner',
				'study_time'           => '35 minutes',
				'japanese_key_term'    => '複線図（ふくせんず / multi-line wiring diagram）',
				'hiragana'             => 'ふくせんず',
				'english_meaning'      => 'Multi-line wiring diagram',
				'simple_explanation'   => 'In series circuits, values add in a straight path. In parallel circuits, the current divides and the total resistance behaves differently.',
				'detailed_explanation' => 'Many written exam questions hide circuit behavior inside a short sentence. If the circuit is series, current stays the same and resistance adds. If it is parallel, voltage stays the same across branches and current divides. Memorize these patterns before trying harder calculation questions.',
				'key_vocabulary'       => "- 単線図（たんせんず / single-line diagram）\n- 複線図（ふくせんず / multi-line wiring diagram）\n- 直列（ちょくれつ / series）\n- 並列（へいれつ / parallel）",
				'formula'              => 'Series: Rtotal = R1 + R2 + R3\nParallel: 1 / Rtotal = 1 / R1 + 1 / R2 + 1 / R3',
				'worked_example'       => 'Two resistors, 4 Ω and 6 Ω, in series make 10 Ω total. In parallel, the total resistance is lower than either single resistor.',
				'exam_tips'            => 'If the question says the current has several paths, think parallel. If the current has one path, think series.',
				'common_trap'          => 'A parallel circuit does not add resistances the same way as a series circuit.',
				'site_reality'         => 'On site, wiring diagrams and actual branch connections must match. The written exam checks whether you can read both.',
				'practice_question'    => 'What is total resistance for two series resistors of 3 Ω and 7 Ω?',
				'answer'               => '10 Ω',
				'answer_explanation'   => 'In series, resistance adds directly: 3 + 7 = 10 Ω.',
				'content'              => '<p>This lesson helps you translate circuit behavior into quick exam answers. Series and parallel are basic ideas that appear in many different forms.</p>',
			),
			array(
				'order'                => 4,
				'title'                => 'Lesson 4: AC, DC, Frequency, and Phase Basics',
				'excerpt'              => 'Understand alternating current, direct current, frequency, single-phase supply, and three-phase supply at a practical level.',
				'categories'           => array( 'electrical-theory', 'symbols-and-diagrams' ),
				'difficulty'           => 'Intermediate',
				'study_time'           => '45 minutes',
				'japanese_key_term'    => '交流（こうりゅう / alternating current）',
				'hiragana'             => 'こうりゅう',
				'english_meaning'      => 'Alternating Current',
				'simple_explanation'   => 'AC changes direction repeatedly. DC flows in one direction only. Frequency tells you how many cycles happen each second.',
				'detailed_explanation' => 'Japanese residential wiring uses AC. The written exam may ask about frequency, phase, or why certain equipment needs AC or DC. Use concrete examples: household power is AC, batteries are DC, and industrial equipment may use three-phase power for motors.',
				'key_vocabulary'       => "- 交流（こうりゅう / alternating current）\n- 直流（ちょくりゅう / direct current）\n- 周波数（しゅうはすう / frequency）\n- 単相（たんそう / single-phase）\n- 三相（さんそう / three-phase）",
				'formula'              => 'Frequency is measured in Hz. Power systems in Japan commonly use 50 Hz or 60 Hz depending on region.',
				'worked_example'       => 'A battery-powered flashlight uses DC. A home outlet supplies AC, so the wiring and equipment must be suitable for AC power.',
				'exam_tips'            => 'If the problem mentions homes, outlets, or common Japanese wiring, expect AC unless the problem says otherwise.',
				'common_trap'          => 'Do not assume frequency changes the voltage value. Frequency describes how often the waveform repeats.',
				'site_reality'         => 'On Japanese job sites, electricians often distinguish AC distribution, DC control circuits, and equipment ratings very carefully.',
				'practice_question'    => 'Which power type does a battery provide?',
				'answer'               => 'DC',
				'answer_explanation'   => 'A battery supplies direct current, which flows in one direction.',
				'content'              => '<p>Frequency and phase are easier to understand if you connect them to real equipment instead of memorizing words alone.</p>',
			),
			array(
				'order'                => 5,
				'title'                => 'Lesson 5: Electrical Calculations and Units',
				'excerpt'              => 'Practice breaker capacity basics, voltage drop basics, and unit conversion methods that are common in exam questions.',
				'categories'           => array( 'electrical-calculations', 'exam-practice' ),
				'difficulty'           => 'Intermediate',
				'study_time'           => '50 minutes',
				'japanese_key_term'    => '電力（でんりょく / power）',
				'hiragana'             => 'でんりょく',
				'english_meaning'      => 'Power',
				'simple_explanation'   => 'Many exam problems are really unit problems. If you can convert, substitute, and keep track of scale, the calculation becomes much easier.',
				'detailed_explanation' => 'A Class 2 exam problem may combine amps, watts, volts, and ohms in one question. It may also ask about breaker capacity, wire size basics, or voltage drop. Do not rush. Identify the known values, select the formula, and check the units after calculation.',
				'key_vocabulary'       => "- kW（kilowatt）\n- W（watt）\n- A（ampere）\n- V（volt）\n- Ω（ohm）",
				'formula'              => '1 kW = 1000 W\n1 A = 1000 mA\nVoltage drop is often estimated from current, resistance, and cable length.',
				'worked_example'       => 'Convert 2.5 kW to watts: 2.5 × 1000 = 2500 W.',
				'exam_tips'            => 'When the question has mixed units, convert first and then calculate.',
				'common_trap'          => 'Do not leave answers in the wrong scale. A 2500 W device is not 2.5 W.',
				'site_reality'         => 'On site, correct unit handling matters for load planning, breaker sizing, and equipment selection.',
				'practice_question'    => 'Convert 750 mA to amperes.',
				'answer'               => '0.75 A',
				'answer_explanation'   => 'Divide by 1000: 750 mA = 0.75 A.',
				'content'              => '<p>This lesson is about exam technique as much as math. Unit conversion is a common place where beginners lose points.</p>',
			),
			array(
				'order'                => 6,
				'title'                => 'Lesson 6: Wiring Diagrams, Symbols, and Cable Identification',
				'excerpt'              => 'Learn how to read single-line diagrams, multi-line diagrams, switches, outlets, and grounding symbols.',
				'categories'           => array( 'wiring-methods', 'symbols-and-diagrams', 'japanese-vocabulary' ),
				'difficulty'           => 'Intermediate',
				'study_time'           => '55 minutes',
				'japanese_key_term'    => '単線図（たんせんず / single-line diagram）',
				'hiragana'             => 'たんせんず',
				'english_meaning'      => 'Single-line diagram',
				'simple_explanation'   => 'The diagram language used in the written exam is simplified, but it still follows real wiring logic.',
				'detailed_explanation' => 'This lesson links symbols to the real wiring methods used in Japanese houses and small buildings. Learn how to identify switch symbols, outlet symbols, junction boxes, grounding marks, cable labels, and how a multi-line diagram expands the single-line view.',
				'key_vocabulary'       => "- 単線図（たんせんず / single-line diagram）\n- 複線図（ふくせんず / multi-line wiring diagram）\n- 接地記号（せっちきごう / grounding symbol）\n- 回路（かいろ / circuit）",
				'formula'              => 'Symbols do not use a numeric formula, but the rule is: read the symbol first, then trace the conductor path.',
				'worked_example'       => 'If a lamp is connected through a switch, the switch should interrupt the live conductor according to the wiring diagram.',
				'exam_tips'            => 'Pay attention to the symbol shape and the direction of the line. One small symbol can change the answer.',
				'common_trap'          => 'Do not guess from the picture alone. A single-line diagram is a compressed representation, not a photograph.',
				'site_reality'         => 'In real construction work, electricians must translate between diagrams, actual cables, and inspection requirements.',
				'practice_question'    => 'What does a grounding symbol usually indicate?',
				'answer'               => 'A safety connection to earth',
				'answer_explanation'   => 'The symbol means the equipment is intentionally connected to earth for safety and fault protection.',
				'content'              => '<p>Diagram reading is one of the fastest ways to improve your written exam score because the symbols repeat a lot.</p>',
			),
			array(
				'order'                => 7,
				'title'                => 'Lesson 7: Tools and Materials',
				'excerpt'              => 'Identify VVF cable, ring sleeves, push-in connectors, outlet boxes, breakers, testers, and crimping tools.',
				'categories'           => array( 'tools-and-materials', 'wiring-methods' ),
				'difficulty'           => 'Beginner',
				'study_time'           => '30 minutes',
				'japanese_key_term'    => 'リングスリーブ（りんぐすりーぶ / ring sleeve）',
				'hiragana'             => 'りんぐすりーぶ',
				'english_meaning'      => 'Ring sleeve',
				'simple_explanation'   => 'The exam expects you to know common tools and materials by shape, purpose, and Japanese name.',
				'detailed_explanation' => 'Many questions show a tool or material and ask for its purpose. You should be able to identify VVF cable, push-in connectors, outlet boxes, circuit breakers, ELCB, MCCB, grounding wire, conduit, insulation testers, voltage testers, and crimping tools.',
				'key_vocabulary'       => "- VVFケーブル（ぶいぶいえふけーぶる / VVF cable）\n- リングスリーブ（りんぐすりーぶ / ring sleeve）\n- 差込形コネクタ（さしこみがたこねくた / push-in connector）\n- アウトレットボックス（あうとれっとぼっくす / outlet box）",
				'formula'              => 'Tool selection is based on the work method, conductor size, and connection type.',
				'worked_example'       => 'If two conductors must be crimped permanently, a ring sleeve may be used. If a quick temporary-looking connection is needed, a push-in connector may be more appropriate.',
				'exam_tips'            => 'Read the purpose of the tool, not only the name. Many answer choices look similar.',
				'common_trap'          => 'Do not mix up breaker types, connector types, and box types just because they are all used in wiring.',
				'site_reality'         => 'On site, using the right material is part of both quality and safety. Written questions often ask which material fits the task.',
				'practice_question'    => 'Which item is commonly used to join conductors by crimping?',
				'answer'               => 'Ring sleeve',
				'answer_explanation'   => 'A ring sleeve is used when conductors must be crimped together with the proper tool.',
				'content'              => '<p>This lesson is practical vocabulary plus identification. If you can recognize the tool or material, many written questions become much easier.</p>',
			),
			array(
				'order'                => 8,
				'title'                => 'Lesson 8: Safety, Grounding, and Breakers',
				'excerpt'              => 'Study electric shock prevention, short circuit prevention, safe isolation, lockout thinking, PPE, and breaker behavior.',
				'categories'           => array( 'safety-and-regulations', 'inspection-and-testing', 'exam-practice' ),
				'difficulty'           => 'Exam Level',
				'study_time'           => '50 minutes',
				'japanese_key_term'    => '漏電遮断器（ろうでんしゃだんき / earth leakage circuit breaker）',
				'hiragana'             => 'ろうでんしゃだんき',
				'english_meaning'      => 'Earth leakage circuit breaker',
				'simple_explanation'   => 'Safety questions are really about preventing shock, fire, and equipment damage. Grounding and breakers work together.',
				'detailed_explanation' => 'This lesson connects the real site safety mindset with the written exam. Know when grounding is needed, how leakage differs from a short circuit, why insulation resistance matters, and how an ELCB or MCCB protects a circuit. PPE, safe isolation, and lockout thinking are part of professional practice.',
				'key_vocabulary'       => "- 接地（せっち / grounding）\n- 漏電（ろうでん / leakage）\n- 短絡（たんらく / short circuit）\n- 絶縁抵抗（ぜつえんていこう / insulation resistance）\n- 漏電遮断器（ろうでんしゃだんき / ELCB）",
				'formula'              => 'Safety rule: isolate, verify, and then work. For exam logic, think fault current, leakage current, and protective device response.',
				'worked_example'       => 'If the insulation becomes damaged and leakage current flows to a metal case, the breaker or ELCB may trip to reduce shock risk.',
				'exam_tips'            => 'If the question asks about protecting people, grounding and leakage protection are often the correct direction.',
				'common_trap'          => 'A breaker does not make unsafe work safe. You must still isolate power and verify the circuit state.',
				'site_reality'         => 'Professional electricians treat every circuit as live until verified otherwise. That mindset is also useful for exam safety questions.',
				'practice_question'    => 'What is the first safe mindset before touching a circuit?',
				'answer'               => 'Assume it may be live until verified',
				'answer_explanation'   => 'Safe work starts with isolation and confirmation, not assumptions.',
				'content'              => '<p>Written exam safety questions usually reward the same habit that real electricians need: isolate, test, protect, and only then continue.</p>',
			),
			array(
				'order'                => 9,
				'title'                => 'Lesson 9: Exam Practice and Common Traps',
				'excerpt'              => 'Use exam-style questions to recognize common mistakes in formula use, diagram reading, and vocabulary translation.',
				'categories'           => array( 'exam-practice', 'japanese-vocabulary', 'safety-and-regulations' ),
				'difficulty'           => 'Exam Level',
				'study_time'           => '45 minutes',
				'japanese_key_term'    => '配線用遮断器（はいせんようしゃだんき / molded case circuit breaker）',
				'hiragana'             => 'はいせんようしゃだんき',
				'english_meaning'      => 'Molded case circuit breaker',
				'simple_explanation'   => 'The final step is learning how the exam hides easy ideas inside confusing wording.',
				'detailed_explanation' => 'This lesson is a review lesson. Revisit Ohm\'s Law, power, circuit behavior, breaker functions, grounding, and vocabulary. Many wrong answers are built from a single confusion: wrong formula, wrong unit, wrong symbol, or wrong Japanese reading.',
				'key_vocabulary'       => "- 配線用遮断器（はいせんようしゃだんき / MCCB）\n- 漏電遮断器（ろうでんしゃだんき / ELCB）\n- 絶縁抵抗（ぜつえんていこう / insulation resistance）\n- 短絡（たんらく / short circuit）",
				'formula'              => 'Exam method: read, translate, identify the topic, choose the formula, calculate, and check the unit.',
				'worked_example'       => 'A question asks for power, but the answer choices are in watts and kilowatts. Convert before selecting the answer.',
				'exam_tips'            => 'Underline the verb in the question: calculate, identify, compare, or explain. The verb tells you what kind of answer is needed.',
				'common_trap'          => 'Students often know the concept but miss the question because the Japanese term is unfamiliar. Read both the hiragana and the English meaning.',
				'site_reality'         => 'On site, accuracy keeps work safe and legal. The written exam trains that same habit through precise wording.',
				'practice_question'    => 'Which device is used for leakage protection rather than only overload protection?',
				'answer'               => 'ELCB',
				'answer_explanation'   => 'The earth leakage circuit breaker is designed to detect leakage current and trip.',
				'content'              => '<p>This lesson closes the loop by turning the whole course into exam practice. Review the patterns until you can recognize them quickly.</p>',
			),
		);
	}

	private static function format_lesson_card_class( string $value ) : string {
		return sanitize_html_class( strtolower( str_replace( ' ', '-', $value ) ) );
	}
}

JEG_Class2_Course::init();
register_activation_hook( __FILE__, array( 'JEG_Class2_Course', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'JEG_Class2_Course', 'deactivate' ) );

}
<?php
/**
 * Plugin Name: Japan Electrician Guide Class 2 Course
 * Description: Structured English study course for the Japanese Class 2 Electrician written exam, with lessons, vocabulary, and quizzes.
 * Version: 1.0.0
 * Author: GitHub Copilot
 * Text Domain: jeg-class2-course
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'JEG_CLASS2_VERSION' ) ) {
	define( 'JEG_CLASS2_VERSION', '1.0.0' );
}

if ( ! defined( 'JEG_CLASS2_FILE' ) ) {
	define( 'JEG_CLASS2_FILE', __FILE__ );
}

if ( ! defined( 'JEG_CLASS2_DIR' ) ) {
	define( 'JEG_CLASS2_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'JEG_CLASS2_URL' ) ) {
	define( 'JEG_CLASS2_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Default course categories.
 *
 * @return array<int, array<string, string>>
 */
function jeg_class2_get_default_categories() {
	return array(
		array(
			'name' => __( 'Electrical Theory', 'jeg-class2-course' ),
			'slug' => 'electrical-theory',
		),
		array(
			'name' => __( 'Electrical Calculations', 'jeg-class2-course' ),
			'slug' => 'electrical-calculations',
		),
		array(
			'name' => __( 'Wiring Methods', 'jeg-class2-course' ),
			'slug' => 'wiring-methods',
		),
		array(
			'name' => __( 'Tools and Materials', 'jeg-class2-course' ),
			'slug' => 'tools-and-materials',
		),
		array(
			'name' => __( 'Safety and Regulations', 'jeg-class2-course' ),
			'slug' => 'safety-and-regulations',
		),
		array(
			'name' => __( 'Japanese Vocabulary', 'jeg-class2-course' ),
			'slug' => 'japanese-vocabulary',
		),
		array(
			'name' => __( 'Symbols and Diagrams', 'jeg-class2-course' ),
			'slug' => 'symbols-and-diagrams',
		),
		array(
			'name' => __( 'Inspection and Testing', 'jeg-class2-course' ),
			'slug' => 'inspection-and-testing',
		),
		array(
			'name' => __( 'Exam Practice', 'jeg-class2-course' ),
			'slug' => 'exam-practice',
		),
	);
}

/**
 * Default vocabulary list.
 *
 * @return array<int, array<string, string>>
 */
function jeg_class2_get_default_vocab() {
	return array(
		array(
			'japanese'     => '電圧',
			'hiragana'     => 'でんあつ',
			'english'      => 'Voltage',
			'explanation'  => 'Electrical pressure that pushes current through a circuit.',
			'example'      => 'A 100 V lighting circuit uses voltage to move current through the load.',
			'exam_point'   => 'Know the symbol V and use voltage in Ohm\'s Law.',
		),
		array(
			'japanese'     => '電流',
			'hiragana'     => 'でんりゅう',
			'english'      => 'Current',
			'explanation'  => 'The flow of electric charge through a conductor.',
			'example'      => 'If resistance drops, current increases when voltage stays the same.',
			'exam_point'   => 'Current is measured in amperes and appears in many calculation questions.',
		),
		array(
			'japanese'     => '抵抗',
			'hiragana'     => 'ていこう',
			'english'      => 'Resistance',
			'explanation'  => 'The opposition to current flow in a circuit.',
			'example'      => 'A longer or thinner wire usually has more resistance.',
			'exam_point'   => 'Resistance is central to series and parallel circuit calculations.',
		),
		array(
			'japanese'     => '電力',
			'hiragana'     => 'でんりょく',
			'english'      => 'Power',
			'explanation'  => 'The rate at which electrical energy is used or converted.',
			'example'      => 'A 500 W heater converts electrical energy into heat.',
			'exam_point'   => 'Use P = VI and remember power is measured in watts.',
		),
		array(
			'japanese'     => '交流',
			'hiragana'     => 'こうりゅう',
			'english'      => 'Alternating Current',
			'explanation'  => 'Current that changes direction periodically.',
			'example'      => 'Japan uses AC for normal building wiring and outlet circuits.',
			'exam_point'   => 'AC frequency questions often appear in the written exam.',
		),
		array(
			'japanese'     => '直流',
			'hiragana'     => 'ちょくりゅう',
			'english'      => 'Direct Current',
			'explanation'  => 'Current that flows in one direction only.',
			'example'      => 'Batteries and control circuits often use DC.',
			'exam_point'   => 'Know the difference between AC and DC symbols and behavior.',
		),
		array(
			'japanese'     => '接地',
			'hiragana'     => 'せっち',
			'english'      => 'Grounding / Earthing',
			'explanation'  => 'Connecting equipment or a system to earth for safety and fault clearing.',
			'example'      => 'Metal appliance frames are grounded to reduce shock risk.',
			'exam_point'   => 'Grounding is a common safety and symbol question.',
		),
		array(
			'japanese'     => '漏電',
			'hiragana'     => 'ろうでん',
			'english'      => 'Electrical leakage',
			'explanation'  => 'Unwanted current flowing to earth or another path outside the intended circuit.',
			'example'      => 'Damaged insulation can cause leakage current.',
			'exam_point'   => 'Leakage current is linked to insulation testing and ELCB operation.',
		),
		array(
			'japanese'     => '短絡',
			'hiragana'     => 'たんらく',
			'english'      => 'Short circuit',
			'explanation'  => 'An abnormal low-resistance path that causes excessive current.',
			'example'      => 'A conductor touching another conductor directly can create a short circuit.',
			'exam_point'   => 'Expect breaker and protection device questions with this term.',
		),
		array(
			'japanese'     => '絶縁抵抗',
			'hiragana'     => 'ぜつえんていこう',
			'english'      => 'Insulation resistance',
			'explanation'  => 'The resistance between conductors or between a conductor and earth through insulation.',
			'example'      => 'A megohmmeter is used to test insulation resistance.',
			'exam_point'   => 'Testing insulation resistance is a written exam safety topic.',
		),
		array(
			'japanese'     => '配線用遮断器',
			'hiragana'     => 'はいせんようしゃだんき',
			'english'      => 'Molded case circuit breaker',
			'explanation'  => 'A breaker that protects wiring and circuits from overcurrent and short circuit.',
			'example'      => 'A distribution board may use an MCCB for main protection.',
			'exam_point'   => 'Know the role of breakers and their protection purpose.',
		),
		array(
			'japanese'     => '漏電遮断器',
			'hiragana'     => 'ろうでんしゃだんき',
			'english'      => 'Earth leakage circuit breaker',
			'explanation'  => 'A device that trips when leakage current is detected.',
			'example'      => 'An ELCB helps prevent electric shock and fire caused by leakage.',
			'exam_point'   => 'Written questions often compare ELCB and overcurrent breakers.',
		),
		array(
			'japanese'     => '接続箱',
			'hiragana'     => 'せつぞくばこ',
			'english'      => 'Junction box',
			'explanation'  => 'A box used to join and protect electrical connections.',
			'example'      => 'Connections in a ceiling circuit may be made inside a junction box.',
			'exam_point'   => 'Remember safe wiring practice and box use in diagrams.',
		),
		array(
			'japanese'     => '複線図',
			'hiragana'     => 'ふくせんず',
			'english'      => 'Multi-line wiring diagram',
			'explanation'  => 'A diagram that shows each conductor separately for practical wiring work.',
			'example'      => 'Use a multi-line diagram to understand switch and lamp connections.',
			'exam_point'   => 'Diagram conversion questions are common in the exam.',
		),
		array(
			'japanese'     => '単線図',
			'hiragana'     => 'たんせんず',
			'english'      => 'Single-line diagram',
			'explanation'  => 'A simplified circuit diagram that represents a circuit with one line.',
			'example'      => 'The single-line diagram is often used in planning and exam questions.',
			'exam_point'   => 'Know how to read symbols and circuit flow from a single-line diagram.',
		),
		array(
			'japanese'     => 'リングスリーブ',
			'hiragana'     => 'りんぐすりーぶ',
			'english'      => 'Ring sleeve',
			'explanation'  => 'A sleeve used for crimping and joining wires safely.',
			'example'      => 'Select the correct ring sleeve size based on conductor count and size.',
			'exam_point'   => 'Selection rules and crimp marks are exam favorites.',
		),
		array(
			'japanese'     => '差込形コネクタ',
			'hiragana'     => 'さしこみがたこねくた',
			'english'      => 'Push-in connector',
			'explanation'  => 'A connector that joins wires by inserting them into a spring clamp mechanism.',
			'example'      => 'Used for quick and neat branch connections in wiring work.',
			'exam_point'   => 'Understand correct conductor stripping and insertion depth.',
		),
		array(
			'japanese'     => 'VVFケーブル',
			'hiragana'     => 'ぶいぶいえふけーぶる',
			'english'      => 'VVF cable',
			'explanation'  => 'A common flat sheathed cable used in Japanese building wiring.',
			'example'      => 'Class 2 written exams often show VVF cable in wiring questions.',
			'exam_point'   => 'Cable identification is essential for tool and wiring questions.',
		),
		array(
			'japanese'     => 'アウトレットボックス',
			'hiragana'     => 'あうとれっとぼっくす',
			'english'      => 'Outlet box',
			'explanation'  => 'A box used to mount outlets, switches, or lighting devices.',
			'example'      => 'It protects the device and keeps wiring organized.',
			'exam_point'   => 'Box choice appears in installation and symbol questions.',
		),
		array(
			'japanese'     => '金属管',
			'hiragana'     => 'きんぞくかん',
			'english'      => 'Metal conduit',
			'explanation'  => 'A metallic pipe used to protect conductors mechanically.',
			'example'      => 'Conduit wiring is used where physical protection is important.',
			'exam_point'   => 'Know where metal conduit is used and why grounding matters.',
		),
	);
}

/**
 * Default quiz questions.
 *
 * @return array<int, array<string, mixed>>
 */
function jeg_class2_get_default_quiz_questions() {
	return array(
		array(
			'question'   => 'What is the correct formula for Ohm\'s Law?',
			'choices'    => array( 'V = IR', 'P = VI', 'I = P / R', 'R = VI' ),
			'answer'     => 'V = IR',
			'explanation' => 'Ohm\'s Law relates voltage, current, and resistance.',
		),
		array(
			'question'   => 'How do you calculate electrical power?',
			'choices'    => array( 'P = VI', 'P = I / V', 'P = R / I', 'P = V + I' ),
			'answer'     => 'P = VI',
			'explanation' => 'Power is voltage multiplied by current.',
		),
		array(
			'question'   => 'What happens to total resistance in a series circuit?',
			'choices'    => array( 'It adds up', 'It becomes zero', 'It stays the same', 'It divides equally by the number of loads' ),
			'answer'     => 'It adds up',
			'explanation' => 'In series, resistances are summed to get total resistance.',
		),
		array(
			'question'   => 'Why is grounding used?',
			'choices'    => array( 'To increase voltage', 'To reduce shock risk and help fault clearing', 'To make resistance zero', 'To stop AC from flowing' ),
			'answer'     => 'To reduce shock risk and help fault clearing',
			'explanation' => 'Grounding helps protect people and equipment during faults.',
		),
		array(
			'question'   => 'What is a short circuit?',
			'choices'    => array( 'A normal current path', 'A high-resistance connection', 'An abnormal low-resistance path causing excessive current', 'A grounded cable only' ),
			'answer'     => 'An abnormal low-resistance path causing excessive current',
			'explanation' => 'A short circuit causes large current and breaker operation.',
		),
		array(
			'question'   => 'What does insulation resistance testing check?',
			'choices'    => array( 'The color of the cable jacket', 'The condition of insulation between conductors or earth', 'The voltage of the supply only', 'The weight of the cable' ),
			'answer'     => 'The condition of insulation between conductors or earth',
			'explanation' => 'The test helps find leakage paths and damaged insulation.',
		),
		array(
			'question'   => 'What is the main function of a breaker?',
			'choices'    => array( 'To increase current', 'To protect circuits from overcurrent or leakage', 'To change AC to DC', 'To measure power factor' ),
			'answer'     => 'To protect circuits from overcurrent or leakage',
			'explanation' => 'Different breakers protect against different fault conditions.',
		),
		array(
			'question'   => 'Why is ring sleeve selection important?',
			'choices'    => array( 'It changes the color of the wire', 'It affects the mechanical and electrical quality of the crimp', 'It lowers the supply voltage', 'It replaces grounding' ),
			'answer'     => 'It affects the mechanical and electrical quality of the crimp',
			'explanation' => 'The correct sleeve size is needed for a reliable crimped connection.',
		),
		array(
			'question'   => 'What does a single-line diagram show?',
			'choices'    => array( 'Each wire separately', 'A simplified circuit using one line', 'Only the building layout', 'Only the breaker number' ),
			'answer'     => 'A simplified circuit using one line',
			'explanation' => 'Single-line diagrams are simplified and used widely in exam questions.',
		),
		array(
			'question'   => 'What is the Japanese term for voltage?',
			'choices'    => array( '電圧', '電流', '抵抗', '電力' ),
			'answer'     => '電圧',
			'explanation' => '電圧 is read でんあつ and means voltage.',
		),
	);
}

/**
 * Default lesson seed data.
 *
 * @return array<int, array<string, mixed>>
 */
function jeg_class2_get_default_lessons() {
	return array(
		array(
			'title'          => 'Lesson 1: Voltage, Current, and Resistance',
			'category_slug'   => 'electrical-theory',
			'difficulty'      => 'beginner',
			'study_time'      => '25 min',
			'key_vocabulary'  => "電圧（でんあつ / voltage）\n電流（でんりゅう / current）\n抵抗（ていこう / resistance）",
			'formula'         => 'V = IR',
			'worked_example'   => 'If voltage is 100 V and resistance is 20 Ω, current is 5 A because I = V / R = 100 / 20.',
			'exam_tips'       => 'Always check the units. Written exam problems often change one value and ask you to solve for another.',
			'practice_questions' => '1) Calculate current when V = 200 V and R = 50 Ω.\n2) If current is 2 A and resistance is 30 Ω, what is voltage?',
			'answer_explanation' => '1) 4 A. 2) 60 V. Rearrange Ohm\'s Law carefully before substituting values.',
			'content'         => 'This lesson introduces the core relationship used in many Class 2 exam questions.',
		),
		array(
			'title'          => 'Lesson 2: Series and Parallel Circuits',
			'category_slug'   => 'electrical-calculations',
			'difficulty'      => 'beginner',
			'study_time'      => '30 min',
			'key_vocabulary'  => "直列（ちょくれつ / series）\n並列（へいれつ / parallel）\n合成抵抗（ごうせい ていこう / total resistance）",
			'formula'         => 'Series: R total = R1 + R2 + R3 / Parallel: 1 / R total = 1 / R1 + 1 / R2 + 1 / R3',
			'worked_example'   => 'Two resistors of 10 Ω and 20 Ω in series give 30 Ω total. In parallel, the total is less than the smallest resistor.',
			'exam_tips'       => 'Parallel circuits are common in lighting and outlet examples. Read the diagram before calculating.',
			'practice_questions' => '1) Find the total resistance of 4 Ω, 6 Ω, and 10 Ω in series.\n2) Why does parallel resistance decrease?',
			'answer_explanation' => '1) 20 Ω. 2) Because current has more than one path, lowering equivalent resistance.',
			'content'         => 'Series and parallel behavior appears in calculation and diagram questions.',
		),
		array(
			'title'          => 'Lesson 3: AC, DC, and Frequency',
			'category_slug'   => 'electrical-theory',
			'difficulty'      => 'intermediate',
			'study_time'      => '20 min',
			'key_vocabulary'  => "交流（こうりゅう / alternating current）\n直流（ちょくりゅう / direct current）\n周波数（しゅうはすう / frequency）",
			'formula'         => 'Frequency is measured in Hz; Japan commonly uses 50 Hz or 60 Hz depending on region.',
			'worked_example'   => 'A 60 Hz source changes direction 60 times per second.',
			'exam_tips'       => 'Frequency is a standard Japan-specific knowledge point. Know that the country uses both 50 Hz and 60 Hz.',
			'practice_questions' => '1) What is the difference between AC and DC?\n2) Which type is used in batteries?',
			'answer_explanation' => 'AC changes direction periodically. DC flows one way. Batteries use DC.',
			'content'         => 'AC and DC questions are usually simple, but they test whether you understand how electricity behaves in practice.',
		),
		array(
			'title'          => 'Lesson 4: Power, Energy, and Heat',
			'category_slug'   => 'electrical-calculations',
			'difficulty'      => 'intermediate',
			'study_time'      => '30 min',
			'key_vocabulary'  => "電力（でんりょく / power）\n電力量（でんりょう / electrical energy）\n発熱（はつねつ / heat generation）",
			'formula'         => 'P = VI, and energy = power × time',
			'worked_example'   => 'A 100 W lamp used for 5 hours consumes 500 Wh of energy.',
			'exam_tips'       => 'Watch unit conversions: W, kW, Wh, and kWh are often mixed in exam questions.',
			'practice_questions' => '1) Find power if V = 100 V and I = 3 A.\n2) How much energy does a 1 kW heater use in 2 hours?',
			'answer_explanation' => '1) 300 W. 2) 2 kWh. Multiply power by time after converting units correctly.',
			'content'         => 'This lesson links the theory of power to real equipment such as heaters and lamps.',
		),
		array(
			'title'          => 'Lesson 5: Wiring Methods, Diagrams, and Symbols',
			'category_slug'   => 'symbols-and-diagrams',
			'difficulty'      => 'intermediate',
			'study_time'      => '35 min',
			'key_vocabulary'  => "単線図（たんせんず / single-line diagram）\n複線図（ふくせんず / multi-line diagram）\n接続箱（せつぞくばこ / junction box）",
			'formula'         => 'No single formula; learn symbol meaning and circuit flow.',
			'worked_example'   => 'A single-line diagram may show a breaker, switch, and lamp in a simplified path. The multi-line diagram shows each conductor separately.',
			'exam_tips'       => 'Many candidates lose points by misunderstanding the difference between single-line and multi-line diagrams.',
			'practice_questions' => '1) Which diagram is more detailed for actual wiring?\n2) Why are symbols important?',
			'answer_explanation' => '1) Multi-line diagram. 2) Symbols let you read wiring quickly and correctly in the exam.',
			'content'         => 'Diagram questions are a core part of written exam preparation and connect theory to installation practice.',
		),
		array(
			'title'          => 'Lesson 6: Tools, Materials, and Cable Identification',
			'category_slug'   => 'tools-and-materials',
			'difficulty'      => 'beginner',
			'study_time'      => '25 min',
			'key_vocabulary'  => "VVFケーブル（ぶいぶいえふけーぶる / VVF cable）\nリングスリーブ（りんぐすりーぶ / ring sleeve）\n差込形コネクタ（さしこみがたこねくた / push-in connector）",
			'formula'         => 'Tool selection is based on conductor size, number of wires, and installation method.',
			'worked_example'   => 'A VVF cable is often used for indoor residential wiring. A ring sleeve is selected according to the number and size of conductors being joined.',
			'exam_tips'       => 'The exam often asks you to identify tools and materials from pictures or short descriptions.',
			'practice_questions' => '1) What does a crimping tool do?\n2) What cable is common in Japanese housing work?',
			'answer_explanation' => '1) It crimps sleeves and terminals. 2) VVF cable is common in building wiring.',
			'content'         => 'Tool and material questions are practical and usually easier if you memorize the Japanese vocabulary clearly.',
		),
		array(
			'title'          => 'Lesson 7: Safety, Grounding, and Breakers',
			'category_slug'   => 'safety-and-regulations',
			'difficulty'      => 'intermediate',
			'study_time'      => '30 min',
			'key_vocabulary'  => "接地（せっち / grounding）\n漏電遮断器（ろうでんしゃだんき / ELCB）\n配線用遮断器（はいせんようしゃだんき / MCCB）",
			'formula'         => 'Safety is based on preventing shock, leakage, and overcurrent.',
			'worked_example'   => 'If insulation is damaged, leakage current may flow and the ELCB can trip to protect the circuit.',
			'exam_tips'       => 'Questions often compare the purpose of grounding, ELCBs, and overcurrent breakers.',
			'practice_questions' => '1) Which device trips on leakage current?\n2) Why is grounding important for metal equipment?',
			'answer_explanation' => '1) ELCB. 2) It reduces shock risk and supports fault protection.',
			'content'         => 'Safety knowledge is not optional. It is part of the written exam and real construction site behavior.',
		),
		array(
			'title'          => 'Lesson 8: Inspection and Testing Basics',
			'category_slug'   => 'inspection-and-testing',
			'difficulty'      => 'intermediate',
			'study_time'      => '25 min',
			'key_vocabulary'  => "絶縁抵抗（ぜつえんていこう / insulation resistance）\n検電器（けんでんき / voltage tester）\n絶縁抵抗計（ぜつえんていこうけい / insulation resistance tester）",
			'formula'         => 'Testing checks whether wiring is safe before energizing a circuit.',
			'worked_example'   => 'A low insulation resistance reading may indicate damaged insulation or leakage.',
			'exam_tips'       => 'Written questions may ask which test should be done before turning power on.',
			'practice_questions' => '1) Why use an insulation tester?\n2) What does a voltage tester confirm?',
			'answer_explanation' => '1) To verify insulation condition. 2) It confirms whether voltage is present.',
			'content'         => 'Inspection and testing are key safety steps in Japanese electrical work.',
		),
		array(
			'title'          => 'Lesson 9: Exam Practice and Japanese Vocabulary Strategy',
			'category_slug'   => 'exam-practice',
			'difficulty'      => 'exam-level',
			'study_time'      => '40 min',
			'key_vocabulary'  => "単線図（たんせんず / single-line diagram）\n複線図（ふくせんず / multi-line diagram）\n短絡（たんらく / short circuit）\n漏電（ろうでん / leakage）",
			'formula'         => 'Read the question, identify the term, then calculate or select the correct device.',
			'worked_example'   => 'If a question asks about a leaked current protection device, choose the ELCB rather than the MCCB.',
			'exam_tips'       => 'Many mistakes come from reading the Japanese term too quickly. Match hiragana, meaning, and device function carefully.',
			'practice_questions' => '1) What is the English meaning of 短絡?\n2) Which breaker responds to leakage current?',
			'answer_explanation' => '1) Short circuit. 2) The ELCB responds to leakage current.',
			'content'         => 'This lesson helps learners turn Japanese technical words into correct exam answers.',
		),
	);
}

/**
 * Register the content types.
 */
function jeg_class2_register_content_types() {
	$labels = array(
		'name'               => __( 'Lessons', 'jeg-class2-course' ),
		'singular_name'      => __( 'Lesson', 'jeg-class2-course' ),
		'add_new'            => __( 'Add New', 'jeg-class2-course' ),
		'add_new_item'       => __( 'Add New Lesson', 'jeg-class2-course' ),
		'edit_item'          => __( 'Edit Lesson', 'jeg-class2-course' ),
		'new_item'           => __( 'New Lesson', 'jeg-class2-course' ),
		'view_item'          => __( 'View Lesson', 'jeg-class2-course' ),
		'search_items'       => __( 'Search Lessons', 'jeg-class2-course' ),
		'not_found'          => __( 'No lessons found.', 'jeg-class2-course' ),
		'not_found_in_trash'  => __( 'No lessons found in Trash.', 'jeg-class2-course' ),
		'menu_name'          => __( 'Lessons', 'jeg-class2-course' ),
	);

	register_post_type(
		'jeg_lesson',
		array(
			'labels'             => $labels,
			'public'             => true,
			'has_archive'        => false,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-welcome-learn-more',
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'show_in_menu'       => false,
			'capability_type'    => 'post',
			'publicly_queryable' => true,
			'rewrite'            => array( 'slug' => 'jeg-lesson' ),
		)
	);

	register_taxonomy(
		'jeg_course_category',
		array( 'jeg_lesson' ),
		array(
			'labels'            => array(
				'name'          => __( 'Course Categories', 'jeg-class2-course' ),
				'singular_name' => __( 'Course Category', 'jeg-class2-course' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'jeg-course-category' ),
		)
	);
}
add_action( 'init', 'jeg_class2_register_content_types' );

/**
 * Add the default course terms.
 */
function jeg_class2_ensure_default_terms() {
	foreach ( jeg_class2_get_default_categories() as $category ) {
		if ( ! term_exists( $category['slug'], 'jeg_course_category' ) ) {
			wp_insert_term( $category['name'], 'jeg_course_category', array( 'slug' => $category['slug'] ) );
		}
	}
}

/**
 * Get or seed the lesson data.
 *
 * @return array<int, array<string, mixed>>
 */
function jeg_class2_get_seedlessons_for_storage() {
	return jeg_class2_get_default_lessons();
}

/**
 * Seed starter content on activation.
 */
function jeg_class2_seed_content() {
	jeg_class2_register_content_types();
	jeg_class2_ensure_default_terms();

	if ( ! get_option( 'jeg_class2_vocab_entries' ) ) {
		update_option( 'jeg_class2_vocab_entries', jeg_class2_get_default_vocab(), false );
	}

	if ( ! get_option( 'jeg_class2_quiz_questions' ) ) {
		update_option( 'jeg_class2_quiz_questions', jeg_class2_get_default_quiz_questions(), false );
	}

	$existing_lessons = get_posts(
		array(
			'post_type'      => 'jeg_lesson',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $existing_lessons ) ) {
		return;
	}

	foreach ( jeg_class2_get_seedlessons_for_storage() as $lesson ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'jeg_lesson',
				'post_status'  => 'publish',
				'post_title'   => $lesson['title'],
				'post_content' => wp_kses_post( $lesson['content'] ),
				'post_excerpt' => wp_strip_all_tags( wp_trim_words( $lesson['content'], 28, '...' ) ),
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		$term = get_term_by( 'slug', $lesson['category_slug'], 'jeg_course_category' );
		if ( $term && ! is_wp_error( $term ) ) {
			wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'jeg_course_category', false );
		}

		update_post_meta( $post_id, '_jeg_lesson_difficulty', sanitize_key( $lesson['difficulty'] ) );
		update_post_meta( $post_id, '_jeg_lesson_study_time', sanitize_text_field( $lesson['study_time'] ) );
		update_post_meta( $post_id, '_jeg_lesson_key_vocabulary', sanitize_textarea_field( $lesson['key_vocabulary'] ) );
		update_post_meta( $post_id, '_jeg_lesson_formula', sanitize_textarea_field( $lesson['formula'] ) );
		update_post_meta( $post_id, '_jeg_lesson_worked_example', sanitize_textarea_field( $lesson['worked_example'] ) );
		update_post_meta( $post_id, '_jeg_lesson_exam_tips', sanitize_textarea_field( $lesson['exam_tips'] ) );
		update_post_meta( $post_id, '_jeg_lesson_practice_questions', sanitize_textarea_field( $lesson['practice_questions'] ) );
		update_post_meta( $post_id, '_jeg_lesson_answer_explanation', sanitize_textarea_field( $lesson['answer_explanation'] ) );
	}
}
register_activation_hook( __FILE__, 'jeg_class2_seed_content' );

/**
 * Flush rewrite rules on deactivation.
 */
function jeg_class2_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'jeg_class2_deactivate' );

/**
 * Register assets.
 */
function jeg_class2_register_assets() {
	wp_register_style(
		'jeg-class2-course',
		JEG_CLASS2_URL . 'assets/css/style.css',
		array(),
		JEG_CLASS2_VERSION
	);

	wp_register_script(
		'jeg-class2-course',
		JEG_CLASS2_URL . 'assets/js/script.js',
		array(),
		JEG_CLASS2_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'jeg_class2_register_assets' );

/**
 * Add lesson meta boxes.
 *
 * @param string $post_type Post type.
 */
function jeg_class2_add_meta_boxes( $post_type ) {
	if ( 'jeg_lesson' !== $post_type ) {
		return;
	}

	add_meta_box(
		'jeg-class2-lesson-details',
		__( 'Lesson Study Fields', 'jeg-class2-course' ),
		'jeg_class2_render_lesson_meta_box',
		'jeg_lesson',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'jeg_class2_add_meta_boxes' );

/**
 * Render lesson meta box.
 *
 * @param WP_Post $post Post object.
 */
function jeg_class2_render_lesson_meta_box( $post ) {
	wp_nonce_field( 'jeg_class2_save_lesson_meta', 'jeg_class2_lesson_meta_nonce' );

	$difficulty            = get_post_meta( $post->ID, '_jeg_lesson_difficulty', true );
	$study_time            = get_post_meta( $post->ID, '_jeg_lesson_study_time', true );
	$key_vocabulary        = get_post_meta( $post->ID, '_jeg_lesson_key_vocabulary', true );
	$formula               = get_post_meta( $post->ID, '_jeg_lesson_formula', true );
	$worked_example        = get_post_meta( $post->ID, '_jeg_lesson_worked_example', true );
	$exam_tips             = get_post_meta( $post->ID, '_jeg_lesson_exam_tips', true );
	$practice_questions    = get_post_meta( $post->ID, '_jeg_lesson_practice_questions', true );
	$answer_explanation    = get_post_meta( $post->ID, '_jeg_lesson_answer_explanation', true );
	?>
	<div class="jeg-admin-meta-grid">
		<p>
			<label for="jeg_lesson_difficulty"><strong><?php esc_html_e( 'Difficulty Level', 'jeg-class2-course' ); ?></strong></label><br />
			<select id="jeg_lesson_difficulty" name="jeg_lesson_difficulty" class="widefat">
				<option value="beginner" <?php selected( $difficulty, 'beginner' ); ?>><?php esc_html_e( 'Beginner', 'jeg-class2-course' ); ?></option>
				<option value="intermediate" <?php selected( $difficulty, 'intermediate' ); ?>><?php esc_html_e( 'Intermediate', 'jeg-class2-course' ); ?></option>
				<option value="exam-level" <?php selected( $difficulty, 'exam-level' ); ?>><?php esc_html_e( 'Exam Level', 'jeg-class2-course' ); ?></option>
			</select>
		</p>
		<p>
			<label for="jeg_lesson_study_time"><strong><?php esc_html_e( 'Estimated Study Time', 'jeg-class2-course' ); ?></strong></label><br />
			<input type="text" id="jeg_lesson_study_time" name="jeg_lesson_study_time" class="widefat" value="<?php echo esc_attr( $study_time ); ?>" placeholder="25 min" />
		</p>
		<p>
			<label for="jeg_lesson_key_vocabulary"><strong><?php esc_html_e( 'Key Vocabulary', 'jeg-class2-course' ); ?></strong></label><br />
			<textarea id="jeg_lesson_key_vocabulary" name="jeg_lesson_key_vocabulary" class="widefat" rows="4"><?php echo esc_textarea( $key_vocabulary ); ?></textarea>
		</p>
		<p>
			<label for="jeg_lesson_formula"><strong><?php esc_html_e( 'Formula Section', 'jeg-class2-course' ); ?></strong></label><br />
			<textarea id="jeg_lesson_formula" name="jeg_lesson_formula" class="widefat" rows="3"><?php echo esc_textarea( $formula ); ?></textarea>
		</p>
		<p>
			<label for="jeg_lesson_worked_example"><strong><?php esc_html_e( 'Worked Example', 'jeg-class2-course' ); ?></strong></label><br />
			<textarea id="jeg_lesson_worked_example" name="jeg_lesson_worked_example" class="widefat" rows="4"><?php echo esc_textarea( $worked_example ); ?></textarea>
		</p>
		<p>
			<label for="jeg_lesson_exam_tips"><strong><?php esc_html_e( 'Exam Tips', 'jeg-class2-course' ); ?></strong></label><br />
			<textarea id="jeg_lesson_exam_tips" name="jeg_lesson_exam_tips" class="widefat" rows="4"><?php echo esc_textarea( $exam_tips ); ?></textarea>
		</p>
		<p>
			<label for="jeg_lesson_practice_questions"><strong><?php esc_html_e( 'Practice Questions', 'jeg-class2-course' ); ?></strong></label><br />
			<textarea id="jeg_lesson_practice_questions" name="jeg_lesson_practice_questions" class="widefat" rows="4"><?php echo esc_textarea( $practice_questions ); ?></textarea>
		</p>
		<p>
			<label for="jeg_lesson_answer_explanation"><strong><?php esc_html_e( 'Answer Explanation', 'jeg-class2-course' ); ?></strong></label><br />
			<textarea id="jeg_lesson_answer_explanation" name="jeg_lesson_answer_explanation" class="widefat" rows="4"><?php echo esc_textarea( $answer_explanation ); ?></textarea>
		</p>
	</div>
	<?php
}

/**
 * Save lesson meta.
 *
 * @param int $post_id Post ID.
 */
function jeg_class2_save_lesson_meta( $post_id ) {
	if ( ! isset( $_POST['jeg_class2_lesson_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jeg_class2_lesson_meta_nonce'] ) ), 'jeg_class2_save_lesson_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$allowed_difficulties = array( 'beginner', 'intermediate', 'exam-level' );
	$difficulty           = isset( $_POST['jeg_lesson_difficulty'] ) ? sanitize_key( wp_unslash( $_POST['jeg_lesson_difficulty'] ) ) : 'beginner';
	if ( ! in_array( $difficulty, $allowed_difficulties, true ) ) {
		$difficulty = 'beginner';
	}

	$meta_map = array(
		'_jeg_lesson_difficulty'         => $difficulty,
		'_jeg_lesson_study_time'         => isset( $_POST['jeg_lesson_study_time'] ) ? sanitize_text_field( wp_unslash( $_POST['jeg_lesson_study_time'] ) ) : '',
		'_jeg_lesson_key_vocabulary'     => isset( $_POST['jeg_lesson_key_vocabulary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jeg_lesson_key_vocabulary'] ) ) : '',
		'_jeg_lesson_formula'            => isset( $_POST['jeg_lesson_formula'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jeg_lesson_formula'] ) ) : '',
		'_jeg_lesson_worked_example'     => isset( $_POST['jeg_lesson_worked_example'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jeg_lesson_worked_example'] ) ) : '',
		'_jeg_lesson_exam_tips'          => isset( $_POST['jeg_lesson_exam_tips'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jeg_lesson_exam_tips'] ) ) : '',
		'_jeg_lesson_practice_questions' => isset( $_POST['jeg_lesson_practice_questions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jeg_lesson_practice_questions'] ) ) : '',
		'_jeg_lesson_answer_explanation' => isset( $_POST['jeg_lesson_answer_explanation'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jeg_lesson_answer_explanation'] ) ) : '',
	);

	foreach ( $meta_map as $meta_key => $meta_value ) {
		update_post_meta( $post_id, $meta_key, $meta_value );
	}
}
add_action( 'save_post_jeg_lesson', 'jeg_class2_save_lesson_meta' );

/**
 * Get lesson meta in a normalized array.
 *
 * @param int $post_id Post ID.
 * @return array<string, string>
 */
function jeg_class2_get_lesson_meta( $post_id ) {
	return array(
		'difficulty'        => get_post_meta( $post_id, '_jeg_lesson_difficulty', true ),
		'study_time'        => get_post_meta( $post_id, '_jeg_lesson_study_time', true ),
		'key_vocabulary'    => get_post_meta( $post_id, '_jeg_lesson_key_vocabulary', true ),
		'formula'           => get_post_meta( $post_id, '_jeg_lesson_formula', true ),
		'worked_example'     => get_post_meta( $post_id, '_jeg_lesson_worked_example', true ),
		'exam_tips'         => get_post_meta( $post_id, '_jeg_lesson_exam_tips', true ),
		'practice_questions' => get_post_meta( $post_id, '_jeg_lesson_practice_questions', true ),
		'answer_explanation' => get_post_meta( $post_id, '_jeg_lesson_answer_explanation', true ),
	);
}

/**
 * Render lesson detail markup.
 *
 * @param WP_Post $lesson Lesson object.
 * @return string
 */
function jeg_class2_render_lesson_detail( $lesson ) {
	$meta = jeg_class2_get_lesson_meta( $lesson->ID );
	$terms = get_the_terms( $lesson, 'jeg_course_category' );
	$category_name = '';

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$term_names = wp_list_pluck( $terms, 'name' );
		$category_name = implode( ', ', $term_names );
	}

	$pretty_difficulty = array(
		'beginner'    => __( 'Beginner', 'jeg-class2-course' ),
		'intermediate'=> __( 'Intermediate', 'jeg-class2-course' ),
		'exam-level'  => __( 'Exam Level', 'jeg-class2-course' ),
	);

	ob_start();
	?>
	<section id="jeg-course-detail" class="jeg-lesson-detail-card">
		<div class="jeg-lesson-detail-header">
			<div>
				<p class="jeg-eyebrow"><?php echo esc_html( $category_name ); ?></p>
				<h2><?php echo esc_html( get_the_title( $lesson ) ); ?></h2>
			</div>
			<div class="jeg-lesson-detail-badges">
				<span class="jeg-badge jeg-badge-difficulty"><?php echo esc_html( $pretty_difficulty[ $meta['difficulty'] ] ?? ucfirst( $meta['difficulty'] ) ); ?></span>
				<span class="jeg-badge"><?php echo esc_html( $meta['study_time'] ); ?></span>
			</div>
		</div>
		<div class="jeg-lesson-detail-body">
			<div class="jeg-lesson-block">
				<h3><?php esc_html_e( 'Lesson Content', 'jeg-class2-course' ); ?></h3>
				<div class="jeg-rich-text"><?php echo wp_kses_post( apply_filters( 'the_content', $lesson->post_content ) ); ?></div>
			</div>
			<div class="jeg-lesson-block">
				<h3><?php esc_html_e( 'Japanese Key Term', 'jeg-class2-course' ); ?></h3>
				<p><?php echo esc_html( trim( wp_strip_all_tags( $meta['key_vocabulary'] ) ) ); ?></p>
			</div>
			<div class="jeg-lesson-grid-2">
				<div class="jeg-lesson-block">
					<h3><?php esc_html_e( 'Formula', 'jeg-class2-course' ); ?></h3>
					<p><?php echo nl2br( esc_html( $meta['formula'] ) ); ?></p>
				</div>
				<div class="jeg-lesson-block">
					<h3><?php esc_html_e( 'Worked Example', 'jeg-class2-course' ); ?></h3>
					<p><?php echo nl2br( esc_html( $meta['worked_example'] ) ); ?></p>
				</div>
			</div>
			<div class="jeg-lesson-grid-2">
				<div class="jeg-lesson-block">
					<h3><?php esc_html_e( 'Exam Trap', 'jeg-class2-course' ); ?></h3>
					<p><?php echo nl2br( esc_html( $meta['exam_tips'] ) ); ?></p>
				</div>
				<div class="jeg-lesson-block">
					<h3><?php esc_html_e( 'Site Reality in Japan', 'jeg-class2-course' ); ?></h3>
					<p><?php echo esc_html__( 'In real Japanese construction work, reading the label, matching the diagram, and checking the protection device are part of the job, not just the exam.', 'jeg-class2-course' ); ?></p>
				</div>
			</div>
			<div class="jeg-lesson-grid-2">
				<div class="jeg-lesson-block">
					<h3><?php esc_html_e( 'Practice Question', 'jeg-class2-course' ); ?></h3>
					<p><?php echo nl2br( esc_html( $meta['practice_questions'] ) ); ?></p>
				</div>
				<div class="jeg-lesson-block">
					<h3><?php esc_html_e( 'Answer and Explanation', 'jeg-class2-course' ); ?></h3>
					<p><?php echo nl2br( esc_html( $meta['answer_explanation'] ) ); ?></p>
				</div>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Get all published lessons.
 *
 * @param string $category_slug Optional category slug.
 * @return WP_Post[]
 */
function jeg_class2_get_lessons( $category_slug = '' ) {
	$args = array(
		'post_type'      => 'jeg_lesson',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	);

	if ( $category_slug ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'jeg_course_category',
				'field'    => 'slug',
				'terms'    => $category_slug,
			),
		);
	}

	$query = new WP_Query( $args );
	return $query->posts;
}

/**
 * Course shortcode.
 *
 * @param array<string, mixed> $atts Shortcode attributes.
 * @return string
 */
function jeg_class2_course_shortcode( $atts ) {
	wp_enqueue_style( 'jeg-class2-course' );
	wp_enqueue_script( 'jeg-class2-course' );

	$atts = shortcode_atts(
		array(
			'category' => '',
		),
		$atts,
		'jeg_class2_course'
	);

	$selected_lesson_id = isset( $_GET['jeg_lesson'] ) ? absint( wp_unslash( $_GET['jeg_lesson'] ) ) : 0;
	$selected_lesson    = $selected_lesson_id ? get_post( $selected_lesson_id ) : null;
	if ( $selected_lesson && 'jeg_lesson' !== $selected_lesson->post_type ) {
		$selected_lesson = null;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'jeg_course_category',
			'hide_empty'  => false,
			'orderby'     => 'name',
		)
	);

	$lessons = jeg_class2_get_lessons( sanitize_title( $atts['category'] ) );
	$lesson_count = count( $lessons );
	$category_count = ! is_wp_error( $terms ) ? count( $terms ) : 0;

	ob_start();
	?>
	<div class="jeg-course-shell" data-jeg-course-wrapper>
		<div class="jeg-course-hero">
			<div class="jeg-course-hero-copy">
				<p class="jeg-eyebrow"><?php esc_html_e( 'Japan Electrician Guide', 'jeg-class2-course' ); ?></p>
				<h1><?php esc_html_e( 'Class 2 Electrician Course', 'jeg-class2-course' ); ?></h1>
				<p class="jeg-course-intro"><?php esc_html_e( 'Study the Japanese 第二種電気工事士 written exam in clear English with vocabulary, calculations, wiring rules, safety, and exam practice.', 'jeg-class2-course' ); ?></p>
			</div>
			<div class="jeg-course-stats">
				<div class="jeg-stat-card"><strong><?php echo esc_html( (string) $lesson_count ); ?></strong><span><?php esc_html_e( 'Lessons', 'jeg-class2-course' ); ?></span></div>
				<div class="jeg-stat-card"><strong><?php echo esc_html( (string) $category_count ); ?></strong><span><?php esc_html_e( 'Categories', 'jeg-class2-course' ); ?></span></div>
				<div class="jeg-stat-card"><strong><?php esc_html_e( 'Beginner', 'jeg-class2-course' ); ?></strong><span><?php esc_html_e( 'Friendly', 'jeg-class2-course' ); ?></span></div>
			</div>
		</div>

		<div class="jeg-course-path">
			<div class="jeg-path-step is-active"><span>1</span><strong><?php esc_html_e( 'Learn theory', 'jeg-class2-course' ); ?></strong><small><?php esc_html_e( 'Voltage, current, resistance, and power', 'jeg-class2-course' ); ?></small></div>
			<div class="jeg-path-step"><span>2</span><strong><?php esc_html_e( 'Read diagrams', 'jeg-class2-course' ); ?></strong><small><?php esc_html_e( 'Single-line and multi-line wiring diagrams', 'jeg-class2-course' ); ?></small></div>
			<div class="jeg-path-step"><span>3</span><strong><?php esc_html_e( 'Use tools safely', 'jeg-class2-course' ); ?></strong><small><?php esc_html_e( 'Materials, testing, and site practice', 'jeg-class2-course' ); ?></small></div>
			<div class="jeg-path-step"><span>4</span><strong><?php esc_html_e( 'Pass the exam', 'jeg-class2-course' ); ?></strong><small><?php esc_html_e( 'Question patterns and answer strategy', 'jeg-class2-course' ); ?></small></div>
		</div>

		<div class="jeg-course-controls">
			<label class="jeg-search-field">
				<span class="screen-reader-text"><?php esc_html_e( 'Search lessons', 'jeg-class2-course' ); ?></span>
				<input type="search" placeholder="<?php esc_attr_e( 'Search lessons...', 'jeg-class2-course' ); ?>" data-jeg-course-search />
			</label>
			<div class="jeg-filter-group" role="tablist" aria-label="<?php esc_attr_e( 'Course categories', 'jeg-class2-course' ); ?>">
				<button type="button" class="jeg-filter-button is-active" data-jeg-course-filter="all"><?php esc_html_e( 'All Lessons', 'jeg-class2-course' ); ?></button>
				<?php if ( ! is_wp_error( $terms ) ) : ?>
					<?php foreach ( $terms as $term ) : ?>
						<button type="button" class="jeg-filter-button" data-jeg-course-filter="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></button>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<div class="jeg-lesson-grid" data-jeg-course-grid>
			<?php foreach ( $lessons as $lesson ) : ?>
				<?php
				$meta   = jeg_class2_get_lesson_meta( $lesson->ID );
				$lesson_terms = get_the_terms( $lesson, 'jeg_course_category' );
				$lesson_category_slug = '';
				$lesson_category_name = '';
				if ( ! empty( $lesson_terms ) && ! is_wp_error( $lesson_terms ) ) {
					$lesson_category_slug = $lesson_terms[0]->slug;
					$lesson_category_name = $lesson_terms[0]->name;
				}
				$difficulty_label = array(
					'beginner'     => __( 'Beginner', 'jeg-class2-course' ),
					'intermediate'  => __( 'Intermediate', 'jeg-class2-course' ),
					'exam-level'    => __( 'Exam Level', 'jeg-class2-course' ),
				);
				$excerpt = has_excerpt( $lesson ) ? get_the_excerpt( $lesson ) : wp_trim_words( wp_strip_all_tags( $lesson->post_content ), 22, '...' );
				$selected_class = ( $selected_lesson && (int) $selected_lesson->ID === (int) $lesson->ID ) ? ' is-selected' : '';
				?>
				<article class="jeg-lesson-card<?php echo esc_attr( $selected_class ); ?>" data-jeg-lesson-card data-category="<?php echo esc_attr( $lesson_category_slug ); ?>" data-title="<?php echo esc_attr( strtolower( get_the_title( $lesson ) ) ); ?>" data-excerpt="<?php echo esc_attr( strtolower( $excerpt ) ); ?>">
					<div class="jeg-lesson-card-top">
						<p class="jeg-card-category"><?php echo esc_html( $lesson_category_name ); ?></p>
						<h2><?php echo esc_html( get_the_title( $lesson ) ); ?></h2>
						<p class="jeg-card-excerpt"><?php echo esc_html( $excerpt ); ?></p>
					</div>
					<div class="jeg-card-meta">
						<span><?php echo esc_html( $difficulty_label[ $meta['difficulty'] ] ?? ucfirst( $meta['difficulty'] ) ); ?></span>
						<span><?php echo esc_html( $meta['study_time'] ); ?></span>
					</div>
					<div class="jeg-card-actions">
						<a class="jeg-start-button" href="<?php echo esc_url( add_query_arg( 'jeg_lesson', $lesson->ID ) . '#jeg-course-detail' ); ?>"><?php esc_html_e( 'Start Lesson', 'jeg-class2-course' ); ?></a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $selected_lesson ) : ?>
			<div class="jeg-selected-lesson">
				<?php echo jeg_class2_render_lesson_detail( $selected_lesson ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'jeg_class2_course', 'jeg_class2_course_shortcode' );

/**
 * Get vocabulary entries.
 *
 * @return array<int, array<string, string>>
 */
function jeg_class2_get_vocab_entries() {
	$vocab = get_option( 'jeg_class2_vocab_entries', jeg_class2_get_default_vocab() );
	if ( ! is_array( $vocab ) || empty( $vocab ) ) {
		$vocab = jeg_class2_get_default_vocab();
	}

	/**
	 * Filter the vocabulary entries.
	 *
	 * @param array<int, array<string, string>> $vocab Vocabulary entries.
	 */
	return apply_filters( 'jeg_class2_vocab_entries', $vocab );
}

/**
 * Vocabulary shortcode.
 *
 * @return string
 */
function jeg_class2_vocab_shortcode() {
	wp_enqueue_style( 'jeg-class2-course' );
	wp_enqueue_script( 'jeg-class2-course' );

	$vocab_entries = jeg_class2_get_vocab_entries();

	ob_start();
	?>
	<section class="jeg-vocab-shell" data-jeg-vocab-wrapper>
		<div class="jeg-section-header">
			<p class="jeg-eyebrow"><?php esc_html_e( 'Vocabulary Bank', 'jeg-class2-course' ); ?></p>
			<h2><?php esc_html_e( 'Japanese Technical Vocabulary for the Class 2 Exam', 'jeg-class2-course' ); ?></h2>
			<p><?php esc_html_e( 'Search Japanese words, hiragana readings, English meanings, and exam notes.', 'jeg-class2-course' ); ?></p>
		</div>

		<label class="jeg-search-field jeg-search-field-wide">
			<span class="screen-reader-text"><?php esc_html_e( 'Search vocabulary', 'jeg-class2-course' ); ?></span>
			<input type="search" placeholder="<?php esc_attr_e( 'Search vocabulary...', 'jeg-class2-course' ); ?>" data-jeg-vocab-search />
		</label>

		<div class="jeg-table-wrap">
			<table class="jeg-vocab-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Japanese', 'jeg-class2-course' ); ?></th>
						<th><?php esc_html_e( 'Hiragana / Reading', 'jeg-class2-course' ); ?></th>
						<th><?php esc_html_e( 'English Meaning', 'jeg-class2-course' ); ?></th>
						<th><?php esc_html_e( 'Technical Explanation', 'jeg-class2-course' ); ?></th>
						<th><?php esc_html_e( 'Example Sentence', 'jeg-class2-course' ); ?></th>
						<th><?php esc_html_e( 'Exam Relevance', 'jeg-class2-course' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $vocab_entries as $entry ) : ?>
						<?php
						$search_text = strtolower( implode( ' ', array_map( 'wp_strip_all_tags', $entry ) ) );
						?>
						<tr data-jeg-vocab-row data-search="<?php echo esc_attr( $search_text ); ?>">
							<td><strong><?php echo esc_html( $entry['japanese'] ); ?></strong></td>
							<td><?php echo esc_html( $entry['hiragana'] ); ?></td>
							<td><?php echo esc_html( $entry['english'] ); ?></td>
							<td><?php echo esc_html( $entry['explanation'] ); ?></td>
							<td><?php echo esc_html( $entry['example'] ); ?></td>
							<td><?php echo esc_html( $entry['exam_point'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'jeg_class2_vocab', 'jeg_class2_vocab_shortcode' );

/**
 * Get quiz questions.
 *
 * @return array<int, array<string, mixed>>
 */
function jeg_class2_get_quiz_questions() {
	$questions = get_option( 'jeg_class2_quiz_questions', jeg_class2_get_default_quiz_questions() );
	if ( ! is_array( $questions ) || empty( $questions ) ) {
		$questions = jeg_class2_get_default_quiz_questions();
	}

	/**
	 * Filter quiz questions before rendering.
	 *
	 * @param array<int, array<string, mixed>> $questions Quiz question list.
	 */
	return apply_filters( 'jeg_class2_quiz_questions', $questions );
}

/**
 * Quiz shortcode.
 *
 * @return string
 */
function jeg_class2_quiz_shortcode() {
	wp_enqueue_style( 'jeg-class2-course' );
	wp_enqueue_script( 'jeg-class2-course' );

	$questions = jeg_class2_get_quiz_questions();
	$total     = count( $questions );

	ob_start();
	?>
	<section class="jeg-quiz-shell" data-jeg-quiz>
		<div class="jeg-section-header">
			<p class="jeg-eyebrow"><?php esc_html_e( 'Quiz Practice', 'jeg-class2-course' ); ?></p>
			<h2><?php esc_html_e( 'Test your understanding with exam-style questions', 'jeg-class2-course' ); ?></h2>
			<p><?php esc_html_e( 'Answer each question, check the explanation, and track your score as you go.', 'jeg-class2-course' ); ?></p>
		</div>

		<div class="jeg-quiz-scorebar">
			<strong><?php esc_html_e( 'Score', 'jeg-class2-course' ); ?>:</strong>
			<span data-jeg-quiz-score>0</span>
			<span>/</span>
			<span><?php echo esc_html( (string) $total ); ?></span>
		</div>

		<div class="jeg-quiz-list">
			<?php foreach ( $questions as $index => $question ) : ?>
				<div class="jeg-quiz-card" data-jeg-quiz-question data-correct="<?php echo esc_attr( $question['answer'] ); ?>" data-explanation="<?php echo esc_attr( $question['explanation'] ); ?>">
					<p class="jeg-quiz-number"><?php echo esc_html( sprintf( __( 'Question %d', 'jeg-class2-course' ), $index + 1 ) ); ?></p>
					<h3><?php echo esc_html( $question['question'] ); ?></h3>
					<div class="jeg-quiz-options">
						<?php foreach ( $question['choices'] as $choice ) : ?>
							<button type="button" class="jeg-quiz-option" data-jeg-quiz-option="<?php echo esc_attr( $choice ); ?>"><?php echo esc_html( $choice ); ?></button>
						<?php endforeach; ?>
					</div>
					<div class="jeg-quiz-feedback" aria-live="polite"></div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'jeg_class2_quiz', 'jeg_class2_quiz_shortcode' );

/**
 * Admin menu page.
 */
function jeg_class2_admin_menu() {
	add_menu_page(
		__( 'JEG Class 2 Course', 'jeg-class2-course' ),
		__( 'JEG Class 2 Course', 'jeg-class2-course' ),
		'manage_options',
		'jeg-class2-course',
		'jeg_class2_render_admin_page',
		'dashicons-welcome-learn-more',
		26
	);
}
add_action( 'admin_menu', 'jeg_class2_admin_menu' );

/**
 * Render the admin page.
 */
function jeg_class2_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$terms = get_terms(
		array(
			'taxonomy'  => 'jeg_course_category',
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);
	?>
	<div class="wrap jeg-admin-wrap">
		<h1><?php esc_html_e( 'JEG Class 2 Course', 'jeg-class2-course' ); ?></h1>
		<p><?php esc_html_e( 'This plugin provides a beginner-friendly online study course for the Japanese 第二種電気工事士 written exam.', 'jeg-class2-course' ); ?></p>

		<div class="jeg-admin-panel">
			<h2><?php esc_html_e( 'Plugin Overview', 'jeg-class2-course' ); ?></h2>
			<p><?php esc_html_e( 'Use lessons for structured study, vocabulary for technical terms, and quizzes for quick exam practice.', 'jeg-class2-course' ); ?></p>
		</div>

		<div class="jeg-admin-panel">
			<h2><?php esc_html_e( 'Shortcode Instructions', 'jeg-class2-course' ); ?></h2>
			<p><code>[jeg_class2_course]</code> <?php esc_html_e( 'displays the full course homepage with filters, lesson cards, and selected lesson detail.', 'jeg-class2-course' ); ?></p>
			<p><code>[jeg_class2_vocab]</code> <?php esc_html_e( 'displays the searchable vocabulary table.', 'jeg-class2-course' ); ?></p>
			<p><code>[jeg_class2_quiz]</code> <?php esc_html_e( 'displays the starter multiple-choice quiz.', 'jeg-class2-course' ); ?></p>
		</div>

		<div class="jeg-admin-panel">
			<h2><?php esc_html_e( 'Course Categories', 'jeg-class2-course' ); ?></h2>
			<ul>
				<?php if ( ! is_wp_error( $terms ) ) : ?>
					<?php foreach ( $terms as $term ) : ?>
						<li><?php echo esc_html( $term->name ); ?></li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>

		<div class="jeg-admin-panel">
			<h2><?php esc_html_e( 'How to Add Lessons', 'jeg-class2-course' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Go to Lessons in the WordPress admin.', 'jeg-class2-course' ); ?></li>
				<li><?php esc_html_e( 'Add a lesson title, content, and choose a course category.', 'jeg-class2-course' ); ?></li>
				<li><?php esc_html_e( 'Fill in difficulty, study time, vocabulary, formula, example, tips, questions, and explanation in the lesson fields.', 'jeg-class2-course' ); ?></li>
			</ol>
		</div>

		<div class="jeg-admin-panel">
			<h2><?php esc_html_e( 'How to Display Vocabulary and Quiz', 'jeg-class2-course' ); ?></h2>
			<p><?php esc_html_e( 'Place the vocabulary shortcode on a study page or glossary page.', 'jeg-class2-course' ); ?></p>
			<p><?php esc_html_e( 'Place the quiz shortcode on a practice page or lesson sidebar.', 'jeg-class2-course' ); ?></p>
		</div>

		<div class="jeg-admin-panel">
			<h2><?php esc_html_e( 'Future Expansion Notes', 'jeg-class2-course' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Add database-stored quiz questions with difficulty filters.', 'jeg-class2-course' ); ?></li>
				<li><?php esc_html_e( 'Add lesson progress tracking for logged-in students.', 'jeg-class2-course' ); ?></li>
				<li><?php esc_html_e( 'Add image-based wiring diagrams and printable worksheets.', 'jeg-class2-course' ); ?></li>
				<li><?php esc_html_e( 'Expand the vocabulary bank with more exam terms and kanji practice.', 'jeg-class2-course' ); ?></li>
			</ul>
		</div>
	</div>
	<?php
}

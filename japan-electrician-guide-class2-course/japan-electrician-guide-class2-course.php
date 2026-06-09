<?php
/**
 * Plugin Name: Japan Electrician Guide Class 2 Course
 * Description: Structured English-language study course for the Japanese Class 2 Electrician written exam.
 * Version: 1.3.0
 * Author: Ben Merritt
 * Text Domain: jeg-class2-course
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'JEG_CLASS2_DIR' ) ) {
	define( 'JEG_CLASS2_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! class_exists( 'JEG_Class2_Course' ) ) {

final class JEG_Class2_Course {
	const VERSION       = '1.3.0';
	const TEXT_DOMAIN   = 'jeg-class2-course';
	const CPT           = 'jeg_lesson';
	const TAXONOMY      = 'jeg_course_category';
	const META_PREFIX   = '_jeg_class2_';
	const OPTION_SEEDED = 'jeg_class2_course_seeded';
	const OPTION_VOCAB  = 'jeg_class2_vocab_entries';
	const OPTION_QUIZ   = 'jeg_class2_quiz_questions';
	const OPTION_DATA_VERSION = 'jeg_class2_data_version';
	const DATA_VERSION        = '1.3.0';

	public static function init() : void {
		add_action( 'init', array( __CLASS__, 'register_types' ) );
		add_action( 'init', array( __CLASS__, 'maybe_seed_updates' ), 20 );
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

	public static function register_types() : void {
		register_post_type(
			self::CPT,
			array(
				'labels' => array(
					'name'               => __( 'Lessons', self::TEXT_DOMAIN ),
					'singular_name'      => __( 'Lesson', self::TEXT_DOMAIN ),
					'add_new_item'       => __( 'Add New Lesson', self::TEXT_DOMAIN ),
					'edit_item'          => __( 'Edit Lesson', self::TEXT_DOMAIN ),
					'new_item'           => __( 'New Lesson', self::TEXT_DOMAIN ),
					'view_item'          => __( 'View Lesson', self::TEXT_DOMAIN ),
					'search_items'       => __( 'Search Lessons', self::TEXT_DOMAIN ),
					'not_found'          => __( 'No lessons found', self::TEXT_DOMAIN ),
					'not_found_in_trash' => __( 'No lessons found in Trash', self::TEXT_DOMAIN ),
					'menu_name'          => __( 'Lessons', self::TEXT_DOMAIN ),
				),
				'public'             => true,
				'show_in_rest'       => true,
				'has_archive'        => true,
				'rewrite'            => array( 'slug' => 'class-2-electrician-lessons' ),
				'menu_icon'          => 'dashicons-welcome-learn-more',
				'supports'           => array( 'title', 'editor', 'excerpt', 'author', 'revisions' ),
				'publicly_queryable' => true,
				'show_in_menu'       => true,
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			array( self::CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'Course Categories', self::TEXT_DOMAIN ),
					'singular_name' => __( 'Course Category', self::TEXT_DOMAIN ),
					'all_items'     => __( 'All Categories', self::TEXT_DOMAIN ),
					'menu_name'     => __( 'Course Categories', self::TEXT_DOMAIN ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'class-2-course-category' ),
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
			array( __CLASS__, 'render_meta_box' ),
			self::CPT,
			'normal',
			'high'
		);
	}

	public static function render_meta_box( WP_Post $post ) : void {
		wp_nonce_field( 'jeg_class2_save_lesson_meta', 'jeg_class2_lesson_nonce' );
		$fields = self::lesson_meta_fields();
		?>
		<div class="jeg-admin-fields">
			<p>
				<label for="jeg_class2_difficulty"><strong><?php echo esc_html__( 'Difficulty', self::TEXT_DOMAIN ); ?></strong></label><br>
				<select id="jeg_class2_difficulty" name="jeg_class2_difficulty" class="widefat">
					<?php foreach ( array( 'Beginner', 'Intermediate', 'Exam Level' ) as $level ) : ?>
						<option value="<?php echo esc_attr( $level ); ?>" <?php selected( get_post_meta( $post->ID, self::META_PREFIX . 'difficulty', true ), $level ); ?>><?php echo esc_html( $level ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="jeg_class2_study_time"><strong><?php echo esc_html__( 'Estimated Study Time', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_study_time" name="jeg_class2_study_time" value="<?php echo esc_attr( get_post_meta( $post->ID, self::META_PREFIX . 'study_time', true ) ); ?>" placeholder="30 minutes">
			</p>
			<p>
				<label for="jeg_class2_japanese_key_term"><strong><?php echo esc_html__( 'Japanese Key Term', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_japanese_key_term" name="jeg_class2_japanese_key_term" value="<?php echo esc_attr( get_post_meta( $post->ID, self::META_PREFIX . 'japanese_key_term', true ) ); ?>" placeholder="電圧（でんあつ / voltage）">
			</p>
			<p>
				<label for="jeg_class2_hiragana"><strong><?php echo esc_html__( 'Hiragana', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_hiragana" name="jeg_class2_hiragana" value="<?php echo esc_attr( get_post_meta( $post->ID, self::META_PREFIX . 'hiragana', true ) ); ?>">
			</p>
			<p>
				<label for="jeg_class2_english_meaning"><strong><?php echo esc_html__( 'English Meaning', self::TEXT_DOMAIN ); ?></strong></label><br>
				<input type="text" class="widefat" id="jeg_class2_english_meaning" name="jeg_class2_english_meaning" value="<?php echo esc_attr( get_post_meta( $post->ID, self::META_PREFIX . 'english_meaning', true ) ); ?>">
			</p>

			<?php foreach ( $fields as $key => $label ) : ?>
				<?php if ( in_array( $key, array( 'difficulty', 'study_time', 'japanese_key_term', 'hiragana', 'english_meaning' ), true ) ) { continue; } ?>
				<p>
					<label for="jeg_class2_<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
					<textarea class="widefat" rows="<?php echo esc_attr( self::field_rows( $key ) ); ?>" id="jeg_class2_<?php echo esc_attr( $key ); ?>" name="jeg_class2_<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( get_post_meta( $post->ID, self::META_PREFIX . $key, true ) ); ?></textarea>
				</p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public static function save_lesson_meta( int $post_id, WP_Post $post ) : void {
		if ( ! isset( $_POST['jeg_class2_lesson_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jeg_class2_lesson_nonce'] ) ), 'jeg_class2_save_lesson_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$values = array(
			'difficulty'           => isset( $_POST['jeg_class2_difficulty'] ) ? sanitize_text_field( wp_unslash( $_POST['jeg_class2_difficulty'] ) ) : '',
			'study_time'           => isset( $_POST['jeg_class2_study_time'] ) ? sanitize_text_field( wp_unslash( $_POST['jeg_class2_study_time'] ) ) : '',
			'japanese_key_term'    => isset( $_POST['jeg_class2_japanese_key_term'] ) ? sanitize_text_field( wp_unslash( $_POST['jeg_class2_japanese_key_term'] ) ) : '',
			'hiragana'             => isset( $_POST['jeg_class2_hiragana'] ) ? sanitize_text_field( wp_unslash( $_POST['jeg_class2_hiragana'] ) ) : '',
			'english_meaning'      => isset( $_POST['jeg_class2_english_meaning'] ) ? sanitize_text_field( wp_unslash( $_POST['jeg_class2_english_meaning'] ) ) : '',
			'simple_explanation'   => self::sanitize_rich_text_field( 'jeg_class2_simple_explanation' ),
			'detailed_explanation' => self::sanitize_rich_text_field( 'jeg_class2_detailed_explanation' ),
			'key_vocabulary'       => self::sanitize_rich_text_field( 'jeg_class2_key_vocabulary' ),
			'formula'              => self::sanitize_rich_text_field( 'jeg_class2_formula' ),
			'worked_example'       => self::sanitize_rich_text_field( 'jeg_class2_worked_example' ),
			'exam_tips'            => self::sanitize_rich_text_field( 'jeg_class2_exam_tips' ),
			'common_trap'          => self::sanitize_rich_text_field( 'jeg_class2_common_trap' ),
			'site_reality'         => self::sanitize_rich_text_field( 'jeg_class2_site_reality' ),
			'practice_question'    => self::sanitize_rich_text_field( 'jeg_class2_practice_question' ),
			'answer'               => self::sanitize_rich_text_field( 'jeg_class2_answer' ),
			'answer_explanation'   => self::sanitize_rich_text_field( 'jeg_class2_answer_explanation' ),
		);

		foreach ( $values as $key => $value ) {
			update_post_meta( $post_id, self::META_PREFIX . $key, $value );
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
		$categories = self::default_categories();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'JEG Class 2 Course', self::TEXT_DOMAIN ); ?></h1>
			<p><?php echo esc_html__( 'Japan Electrician Guide Class 2 Course provides a structured English-language study path for the Japanese 第二種電気工事士 written exam.', self::TEXT_DOMAIN ); ?></p>
			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Plugin Overview', self::TEXT_DOMAIN ); ?></h2>
				<p><?php echo esc_html__( 'Use lessons for structured study, vocabulary for bilingual technical terms, and quiz for quick exam practice.', self::TEXT_DOMAIN ); ?></p>
			</div>
			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Shortcode Instructions', self::TEXT_DOMAIN ); ?></h2>
				<p><strong>[jeg_class2_course]</strong> <?php echo esc_html__( 'shows the course homepage with filters, cards, and selected lesson detail.', self::TEXT_DOMAIN ); ?></p>
				<p><strong>[jeg_class2_vocab]</strong> <?php echo esc_html__( 'shows the searchable vocabulary table.', self::TEXT_DOMAIN ); ?></p>
				<p><strong>[jeg_class2_quiz]</strong> <?php echo esc_html__( 'shows the interactive multiple-choice quiz with score tracking.', self::TEXT_DOMAIN ); ?></p>
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
					<li><?php echo esc_html__( 'Set a title, add the lesson body, and choose a course category.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Fill in the lesson details box with difficulty, study time, vocabulary, formulas, examples, and exam guidance.', self::TEXT_DOMAIN ); ?></li>
				</ol>
			</div>
			<div class="card" style="max-width: 100%;">
				<h2><?php echo esc_html__( 'Future Expansion Notes', self::TEXT_DOMAIN ); ?></h2>
				<ul style="margin-left: 1.25rem;">
					<li><?php echo esc_html__( 'Add database-stored quiz questions with difficulty filters.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Add lesson progress tracking for logged-in students.', self::TEXT_DOMAIN ); ?></li>
					<li><?php echo esc_html__( 'Add image-based wiring diagrams and printable worksheets.', self::TEXT_DOMAIN ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	public static function render_course_shortcode( $atts = array() ) : string {
		self::enqueue_frontend_assets();
		$lessons = self::get_lessons();
		$terms   = get_terms( array( 'taxonomy' => self::TAXONOMY, 'hide_empty' => false, 'orderby' => 'name' ) );
		$selected = isset( $_GET['jeg_lesson'] ) ? absint( wp_unslash( $_GET['jeg_lesson'] ) ) : 0;
		$selected_post = $selected ? get_post( $selected ) : null;
		$base_url = self::current_url();

		ob_start();
		?>
		<section class="jeg-course-shell" data-jeg-course>
			<div class="jeg-course-hero">
				<p class="jeg-eyebrow"><?php echo esc_html__( 'English study course for the Japanese written exam', self::TEXT_DOMAIN ); ?></p>
				<h2><?php echo esc_html__( 'Japan Electrician Guide - Class 2 Electrician Course', self::TEXT_DOMAIN ); ?></h2>
				<p class="jeg-lead"><?php echo esc_html__( 'Study electrical theory, Japanese technical vocabulary, calculations, wiring rules, safety, tools, and exam-style questions in one structured path.', self::TEXT_DOMAIN ); ?></p>
				<div class="jeg-progress-steps">
					<div class="jeg-step"><span>1</span><strong><?php echo esc_html__( 'Learn the concept', self::TEXT_DOMAIN ); ?></strong></div>
					<div class="jeg-step"><span>2</span><strong><?php echo esc_html__( 'Practice calculations', self::TEXT_DOMAIN ); ?></strong></div>
					<div class="jeg-step"><span>3</span><strong><?php echo esc_html__( 'Check exam traps', self::TEXT_DOMAIN ); ?></strong></div>
				</div>
			</div>

			<div class="jeg-course-toolbar">
				<label class="jeg-search-field">
					<span class="screen-reader-text"><?php echo esc_html__( 'Search lessons', self::TEXT_DOMAIN ); ?></span>
					<input type="search" class="jeg-search-input" placeholder="<?php echo esc_attr__( 'Search lessons, vocabulary, formulas, and exam topics', self::TEXT_DOMAIN ); ?>" data-jeg-course-search>
				</label>
				<div class="jeg-filter-row">
					<button type="button" class="jeg-filter-button is-active" data-filter="all"><?php echo esc_html__( 'All Lessons', self::TEXT_DOMAIN ); ?></button>
					<?php if ( ! is_wp_error( $terms ) ) : ?>
						<?php foreach ( $terms as $term ) : ?>
							<button type="button" class="jeg-filter-button" data-filter="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></button>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="jeg-course-summary">
				<div class="jeg-summary-card"><strong><?php echo esc_html( number_format_i18n( count( $lessons ) ) ); ?></strong><span><?php echo esc_html__( 'Starter lessons', self::TEXT_DOMAIN ); ?></span></div>
				<div class="jeg-summary-card"><strong><?php echo esc_html( number_format_i18n( count( self::get_vocab_entries() ) ) ); ?></strong><span><?php echo esc_html__( 'Vocabulary terms', self::TEXT_DOMAIN ); ?></span></div>
				<div class="jeg-summary-card"><strong><?php echo esc_html__( 'Beginner friendly', self::TEXT_DOMAIN ); ?></strong><span><?php echo esc_html__( 'Clear English explanations', self::TEXT_DOMAIN ); ?></span></div>
			</div>

			<div class="jeg-lesson-grid">
				<?php foreach ( $lessons as $lesson ) :
					$lesson_terms = wp_get_post_terms( $lesson->ID, self::TAXONOMY, array( 'fields' => 'slugs' ) );
					$lesson_term_names = wp_get_post_terms( $lesson->ID, self::TAXONOMY, array( 'fields' => 'names' ) );
					$category_slugs = is_array( $lesson_terms ) ? implode( ' ', $lesson_terms ) : '';
					$excerpt = wp_trim_words( wp_strip_all_tags( $lesson->post_excerpt ? $lesson->post_excerpt : $lesson->post_content ), 28, '...' );
					$difficulty = get_post_meta( $lesson->ID, self::META_PREFIX . 'difficulty', true );
					$study_time = get_post_meta( $lesson->ID, self::META_PREFIX . 'study_time', true );
					$detail_url = add_query_arg( 'jeg_lesson', $lesson->ID, $base_url );
					?>
					<article class="jeg-lesson-card<?php echo $selected === (int) $lesson->ID ? ' is-active' : ''; ?>" data-course-card data-category="<?php echo esc_attr( $category_slugs ); ?>" data-title="<?php echo esc_attr( strtolower( $lesson->post_title ) ); ?>" data-excerpt="<?php echo esc_attr( strtolower( $excerpt ) ); ?>">
						<div class="jeg-card-meta-row">
							<span class="jeg-card-tag"><?php echo esc_html( $difficulty ? $difficulty : 'Beginner' ); ?></span>
							<span class="jeg-card-time"><?php echo esc_html( $study_time ? $study_time : '30 minutes' ); ?></span>
						</div>
						<h3><?php echo esc_html( $lesson->post_title ); ?></h3>
						<div class="jeg-card-categories">
							<?php if ( ! empty( $lesson_term_names ) && ! is_wp_error( $lesson_term_names ) ) : ?>
								<?php foreach ( $lesson_term_names as $name ) : ?>
									<span><?php echo esc_html( $name ); ?></span>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<p><?php echo esc_html( $excerpt ); ?></p>
						<a class="jeg-button" href="<?php echo esc_url( $detail_url ); ?>#jeg-course-detail"><?php echo esc_html__( 'Start Lesson', self::TEXT_DOMAIN ); ?></a>
					</article>
				<?php endforeach; ?>
			</div>

			<?php if ( $selected_post instanceof WP_Post && self::CPT === $selected_post->post_type ) : ?>
				<div class="jeg-selected-lesson" id="jeg-course-detail"><?php echo wp_kses_post( self::render_lesson_detail( $selected_post ) ); ?></div>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_vocab_shortcode() : string {
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
						<?php foreach ( $entries as $entry ) : ?>
							<tr data-vocab-row data-search="<?php echo esc_attr( strtolower( implode( ' ', $entry ) ) ); ?>">
								<td><strong><?php echo esc_html( $entry['Japanese'] ); ?></strong></td>
								<td><?php echo esc_html( $entry['Hiragana'] ); ?></td>
								<td><?php echo esc_html( $entry['English'] ); ?></td>
								<td><?php echo esc_html( $entry['Explanation'] ); ?></td>
								<td><?php echo esc_html( $entry['Example'] ); ?></td>
								<td><?php echo esc_html( $entry['Exam Point'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_quiz_shortcode() : string {
		self::enqueue_frontend_assets();
		$questions = self::get_quiz_questions();
		$wiring_count = 0;
		$diagram_count = 0;

		foreach ( $questions as $question ) {
			if ( 'Wiring Diagrams Section' === ( $question['section'] ?? '' ) ) {
				$wiring_count++;
			}

			if ( '' !== trim( (string) ( $question['diagram'] ?? '' ) ) ) {
				$diagram_count++;
			}
		}

		ob_start();
		?>
		<section class="jeg-quiz-shell" data-jeg-quiz>
			<div class="jeg-course-hero jeg-course-hero--compact">
				<p class="jeg-eyebrow"><?php echo esc_html__( 'Quick exam practice', self::TEXT_DOMAIN ); ?></p>
				<h2><?php echo esc_html__( 'Class 2 Electrician Quiz', self::TEXT_DOMAIN ); ?></h2>
				<p class="jeg-lead"><?php echo esc_html( sprintf( __( 'Answer realistic exam-style questions, review detailed explanations, and track your score. This set has %1$d questions with %2$d dedicated wiring diagram items and %3$d text diagram variations.', self::TEXT_DOMAIN ), count( $questions ), $wiring_count, $diagram_count ) ); ?></p>
				<p class="jeg-lead" style="margin-top: 0.5rem;"><?php echo esc_html__( 'These are original practice items built to match common Class 2 written exam patterns.', self::TEXT_DOMAIN ); ?></p>
			</div>
			<div class="jeg-quiz-scoreboard" data-jeg-quiz-score><span><?php echo esc_html__( 'Score', self::TEXT_DOMAIN ); ?></span><strong>0 / <?php echo esc_html( number_format_i18n( count( $questions ) ) ); ?></strong></div>
			<div class="jeg-quiz-list">
				<?php $current_section = ''; ?>
				<?php foreach ( $questions as $index => $question ) : ?>
					<?php if ( $current_section !== $question['section'] ) : ?>
						<?php $current_section = $question['section']; ?>
						<h3 class="jeg-quiz-section-title"><?php echo esc_html( $current_section ); ?></h3>
					<?php endif; ?>
					<div class="jeg-quiz-question" data-question data-correct="<?php echo esc_attr( (string) $question['correct'] ); ?>">
						<h3><?php echo esc_html( ( $index + 1 ) . '. ' . $question['question'] ); ?></h3>
						<?php if ( '' !== trim( $question['diagram'] ) ) : ?>
							<pre class="jeg-quiz-diagram"><?php echo esc_html( $question['diagram'] ); ?></pre>
						<?php endif; ?>
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
			<div class="jeg-quiz-actions"><button type="button" class="jeg-button jeg-button--secondary" data-jeg-quiz-restart><?php echo esc_html__( 'Restart Quiz', self::TEXT_DOMAIN ); ?></button></div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function activate() : void {
		self::register_types();
		self::seed_default_content();
		flush_rewrite_rules();
	}

	public static function deactivate() : void {
		flush_rewrite_rules();
	}

	public static function maybe_seed_updates() : void {
		$stored_version = (string) get_option( self::OPTION_DATA_VERSION, '' );
		if ( self::DATA_VERSION === $stored_version ) {
			return;
		}

		self::seed_default_content();
		update_option( self::OPTION_DATA_VERSION, self::DATA_VERSION, false );
	}

	private static function seed_default_content() : void {
		foreach ( self::default_categories() as $category ) {
			if ( ! term_exists( $category['slug'], self::TAXONOMY ) ) {
				wp_insert_term( $category['name'], self::TAXONOMY, array( 'slug' => $category['slug'] ) );
			}
		}

		$default_vocab = self::default_vocab_entries();
		$stored_vocab  = get_option( self::OPTION_VOCAB, array() );
		if ( ! is_array( $stored_vocab ) || count( $stored_vocab ) < count( $default_vocab ) ) {
			update_option( self::OPTION_VOCAB, $default_vocab, false );
		}

		$default_quiz = self::default_quiz_questions();
		$stored_quiz  = get_option( self::OPTION_QUIZ, array() );
		if ( ! is_array( $stored_quiz ) || count( $stored_quiz ) < count( $default_quiz ) ) {
			update_option( self::OPTION_QUIZ, $default_quiz, false );
		}

		$existing_titles = array();
		$existing_posts  = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $existing_posts as $existing_post_id ) {
			$existing_title = get_the_title( $existing_post_id );
			if ( is_string( $existing_title ) && '' !== $existing_title ) {
				$existing_titles[ $existing_title ] = true;
			}
		}

		foreach ( self::default_lessons() as $lesson ) {
			if ( isset( $existing_titles[ $lesson['title'] ] ) ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => self::CPT,
					'post_status'   => 'publish',
					'post_title'    => $lesson['title'],
					'post_content'  => $lesson['content'],
					'post_excerpt'  => $lesson['excerpt'],
					'menu_order'    => (int) $lesson['order'],
				)
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			wp_set_object_terms( $post_id, $lesson['categories'], self::TAXONOMY );

			$meta = array(
				'difficulty'           => $lesson['difficulty'],
				'study_time'           => $lesson['study_time'],
				'japanese_key_term'    => $lesson['japanese_key_term'],
				'hiragana'             => $lesson['hiragana'],
				'english_meaning'      => $lesson['english_meaning'],
				'simple_explanation'   => $lesson['simple_explanation'],
				'detailed_explanation' => $lesson['detailed_explanation'],
				'key_vocabulary'       => $lesson['key_vocabulary'],
				'formula'              => $lesson['formula'],
				'worked_example'       => $lesson['worked_example'],
				'exam_tips'            => $lesson['exam_tips'],
				'common_trap'          => $lesson['common_trap'],
				'site_reality'         => $lesson['site_reality'],
				'practice_question'    => $lesson['practice_question'],
				'answer'               => $lesson['answer'],
				'answer_explanation'   => $lesson['answer_explanation'],
			);

			foreach ( $meta as $key => $value ) {
				update_post_meta( $post_id, self::META_PREFIX . $key, wp_kses_post( $value ) );
			}

			$existing_titles[ $lesson['title'] ] = true;
		}

		update_option( self::OPTION_SEEDED, 1 );
	}

	private static function get_lessons() : array {
		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			)
		);

		return $query->posts;
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

		$terms = get_the_terms( $lesson, self::TAXONOMY );

		ob_start();
		?>
		<article class="jeg-lesson-detail">
			<div class="jeg-lesson-detail__header">
				<p class="jeg-eyebrow"><?php echo esc_html__( 'Selected lesson', self::TEXT_DOMAIN ); ?></p>
				<h2><?php echo esc_html( $lesson->post_title ); ?></h2>
				<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
					<p class="jeg-detail-categories">
						<?php foreach ( $terms as $term ) : ?>
							<span><?php echo esc_html( $term->name ); ?></span>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</div>
			<div class="jeg-detail-body">
				<div class="jeg-detail-section">
					<h3><?php echo esc_html__( 'Lesson Content', self::TEXT_DOMAIN ); ?></h3>
					<div class="jeg-rich-text"><?php echo wp_kses_post( wpautop( $lesson->post_content ) ); ?></div>
				</div>
				<?php foreach ( $meta as $label => $value ) : ?>
					<?php if ( '' === trim( (string) $value ) ) { continue; } ?>
					<div class="jeg-detail-section">
						<h3><?php echo esc_html( $label ); ?></h3>
						<div class="jeg-rich-text"><?php echo wp_kses_post( wpautop( $value ) ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	private static function get_vocab_entries() : array {
		$entries = get_option( self::OPTION_VOCAB, array() );
		if ( ! is_array( $entries ) || empty( $entries ) ) {
			$entries = self::default_vocab_entries();
		}

		return array_map(
			static function ( $entry ) {
				return array(
					'Japanese'    => (string) ( $entry['Japanese'] ?? '' ),
					'Hiragana'    => (string) ( $entry['Hiragana'] ?? '' ),
					'English'     => (string) ( $entry['English'] ?? '' ),
					'Explanation' => (string) ( $entry['Explanation'] ?? '' ),
					'Example'     => (string) ( $entry['Example'] ?? '' ),
					'Exam Point'  => (string) ( $entry['Exam Point'] ?? '' ),
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
					'section'    => (string) ( $question['section'] ?? 'Core Exam Section' ),
					'diagram'    => (string) ( $question['diagram'] ?? '' ),
					'question'   => (string) ( $question['question'] ?? '' ),
					'choices'    => isset( $question['choices'] ) && is_array( $question['choices'] ) ? array_values( $question['choices'] ) : array(),
					'correct'    => absint( $question['correct'] ?? 0 ),
					'explanation' => (string) ( $question['explanation'] ?? '' ),
				);
			},
			$questions
		);
	}

	private static function default_categories() : array {
		return require JEG_CLASS2_DIR . 'includes/data/categories.php';
	}

	private static function default_vocab_entries() : array {
		return require JEG_CLASS2_DIR . 'includes/data/vocabulary.php';
	}

	private static function default_quiz_questions() : array {
		return require JEG_CLASS2_DIR . 'includes/data/quiz.php';
	}

	private static function default_lessons() : array {
		return require JEG_CLASS2_DIR . 'includes/data/lessons.php';
	}

	private static function lesson_meta_fields() : array {
		return array(
			'difficulty'           => 'Difficulty',
			'study_time'           => 'Estimated Study Time',
			'japanese_key_term'    => 'Japanese Key Term',
			'hiragana'             => 'Hiragana',
			'english_meaning'      => 'English Meaning',
			'simple_explanation'   => 'Simple Explanation',
			'detailed_explanation' => 'Detailed Explanation',
			'key_vocabulary'       => 'Key Vocabulary',
			'formula'              => 'Formula',
			'worked_example'       => 'Worked Example',
			'exam_tips'            => 'Exam Tips',
			'common_trap'          => 'Common Exam Trap',
			'site_reality'         => 'Site Reality in Japan',
			'practice_question'    => 'Practice Question',
			'answer'               => 'Answer',
			'answer_explanation'   => 'Answer Explanation',
		);
	}

	private static function field_rows( string $key ) : int {
		$map = array(
			'simple_explanation'   => 4,
			'detailed_explanation' => 6,
			'key_vocabulary'       => 4,
			'formula'              => 3,
			'worked_example'       => 4,
			'exam_tips'            => 4,
			'common_trap'          => 3,
			'site_reality'         => 3,
			'practice_question'    => 4,
			'answer'               => 3,
			'answer_explanation'   => 4,
		);

		return $map[ $key ] ?? 4;
	}

	private static function sanitize_rich_text_field( string $field_name ) : string {
		return isset( $_POST[ $field_name ] ) ? wp_kses_post( wp_unslash( $_POST[ $field_name ] ) ) : '';
	}

	private static function current_url() : string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		return remove_query_arg( 'jeg_lesson', home_url( $request_uri ) );
	}

	private static function enqueue_frontend_assets() : void {
		wp_enqueue_style( 'jeg-class2-course-style' );
		wp_enqueue_script( 'jeg-class2-course-script' );
	}
}

JEG_Class2_Course::init();
register_activation_hook( __FILE__, array( 'JEG_Class2_Course', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'JEG_Class2_Course', 'deactivate' ) );

}

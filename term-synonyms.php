<?php
/*
Plugin Name: Term Synonyms
Plugin URI:  http://github.com/lgedeon/term-synonyms
Description: This plug creates unbalanced synonyms. So the archive page for automobile might include cars and trucks, but truck might not include automobile.
Version:     0.1
Author:      Luke Gedeon
Author URI:  http://github.com/lgedeon/
*/

/**
 * Has a super-light UI. Implementations can add to the UI as needed.
 *
 * todo: Display name of term not just id
 * todo: Add filter on which taxonomies to add synonyms to
 * todo: use synonyms to add to search results and archive pages
 */

if ( ! class_exists( 'Term_Synonyms' ) ) {

	class Term_Synonyms {
		/**
		 * @var bool|Term_Synonyms
		 */
		protected static $_instance = false;

		/**
		 * Gets the singleton instance of this class - should only get constructed once.
		 *
		 * @return bool|Term_Synonyms
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Constructor -  Wire up actions and filters
		 */
		protected function __construct() {
			add_action( 'init', array( $this, 'action__init' ), 11 );
		}

		public function action__init () {
			$taxonomies = get_taxonomies();
			foreach ( $taxonomies as $taxonomy ) {
				add_action( "{$taxonomy}_add_form_fields",  array( $this, 'action__add_form_fields' ) );
				add_action( "{$taxonomy}_edit_form_fields", array( $this, 'action__edit_form_fields' ), 10, 2 );
			}
			add_action( "created_term", array( $this, 'action__created_edited_term' ), 10, 3 );
			add_action( "edited_term", array( $this, 'action__created_edited_term' ), 10, 3 );
		}

		// No typo here... the add form and the edit form have different markup :(
		public function action__add_form_fields ( $taxonomy ) {
			$taxonomy = get_taxonomy( $taxonomy )->label;
			?>
			<div class="form-field term-synonyms-wrap">
				<label for="term-synonyms">Synonyms</label>
				<input name="term-synonyms" id="term-synonyms" type="text" value="" size="40">
				<p>Comma separated list of related <?php echo $taxonomy; ?>.</p>
			</div>
		<?php }

		public function action__edit_form_fields ( $term, $taxonomy ) {
			$taxonomy = get_taxonomy( $taxonomy )->label;
			$synonyms = (array) get_term_meta( $term->term_id, 'synonyms', true );
			$synonyms = implode( ', ', array_filter( $synonyms, 'is_int' ) );
			?>
			<tr class="form-field term-synonyms-wrap">
				<th scope="row"><label for="term-synonyms">Synonyms</label></th>
				<td><input name="term-synonyms" id="term-synonyms" type="text" value="<?php echo $synonyms; ?>" size="40">
					<p class="description">Comma separated list of <?php echo $taxonomy; ?>.</p></td>
			</tr>
		<?php }

		/*
		 * Catch all save and update actions for all terms. See action__init for what is caught.
		 */
		public function action__created_edited_term ( $term_id, $tt_id, $taxonomy ) {
			$synonym_ids = array();

			$synonyms = isset( $_POST['term-synonyms'] ) ? $_POST['term-synonyms'] : '';
			$synonyms = explode( ',', $synonyms );

			foreach ( $synonyms as $synonym ) {
				if ( is_numeric( $synonym ) ) {
					$synonym = get_term( $synonym, $taxonomy );
				} else {
					$synonym = get_term_by( 'name', trim( $synonym ), $taxonomy );
					if ( empty( $synonym ) ) {
						$synonym = get_term_by( 'slug', trim( $synonym ), $taxonomy );
					}
				}

				if ( isset( $synonym->term_id ) ) {
					$synonym_ids[] = $synonym->term_id;
				}
			}

			update_term_meta( $term_id, 'synonyms', $synonym_ids );
		}

	}

	Term_Synonyms::instance();
}


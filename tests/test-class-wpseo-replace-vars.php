<?php

class WPSEO_Replace_Vars_Test extends WPSEO_UnitTestCase {

	public $user_id = 0;

	public $term_base_info = array(
		array(
			'name'        => 'Test category',
			'taxonomy'    => 'category',
			'description' => 'Test category description',
		),
		array(
			'name'        => 'Test tag',
			'taxonomy'    => 'post_tag',
			'description' => 'Test tag description',
		),
	);

	public $post_base_info = array(
		array(
			'post_title'   => 'Post_without_excerpt',
			'post_excerpt' => '',
			'post_content' => 'Post_Content',
			'post_date'    => '2000-01-01 2:30:00',
		),
		array(
			'post_title'   => 'Post_with_excerpt',
			'post_excerpt' => 'Post_Excerpt',
			'post_content' => 'Post_Content',
			'post_date'    => '2000-01-02 2:30:00',
		),
		array(
			'post_title'   => 'Multipage_post',
			'post_excerpt' => '',
			'post_content' => 'Page 1 <!--nextpage--> Page 2 <!--nextpage--> Page 3',
			'post_date'    => '2000-01-03 2:30:00',
		),
	);

	public $page_base_info = array(
		array(
			'post_type'    => 'page',
			'post_title'   => 'Parent_page',
			'post_content' => 'Page_Content',
			'post_date'    => '2000-01-01 2:30:00',
		),
		array(
			'post_type'    => 'page',
			'post_title'   => 'Child_page 1',
			'post_content' => 'Child_Page_1_Content',
			'post_date'    => '2000-01-02 2:30:00',
		),
		array(
			'post_type'    => 'page',
			'post_title'   => 'GrandChild_page',
			'post_content' => 'GrandChild_Page_Content',
			'post_date'    => '2000-01-03 2:30:00',
		),
		array(
			'post_type'    => 'page',
			'post_title'   => 'GrandChild_page_multipage',
			'post_content' => 'Page 1 <!--nextpage--> Page 2 <!--nextpage--> Page 3',
			'post_date'    => '2000-01-03 2:30:00',
		),
	);

	public $posts = array();
	public $pages = array();
	public $terms = array();


	/**
	 * Provision some options
	 */
	public function setUp() {
		parent::setUp();

		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );

		// create author
		$this->user_id = $this->factory->user->create(
			array(
				'user_login'   => 'User_Login',
				'display_name' => 'User_Nicename',
			)
		);

		// create terms
		foreach ( $this->term_base_info as $k => $term_array ) {
			$this->terms[$k]       = $term_array;
			$this->terms[$k]['id'] = $this->factory->term->create( $term_array );
		}
		unset( $k, $term_array );


		// create posts
		foreach ( $this->post_base_info as $k => $post_array ) {
			$this->posts[$k]                = $post_array;
			$this->posts[$k]['post_author'] = $this->user_id;
			$this->posts[$k]['post_date']   = date( 'Y-m-d H:i:s', strtotime( $post_array['post_date'] ) );
			$this->posts[$k]['id']          = $this->factory->post->create( $this->posts[$k] );
		}
		unset( $k, $post_array );


		// create pages
		$parent = null;
		$child  = null;
		foreach ( $this->page_base_info as $k => $post_array ) {
			$this->pages[$k]                = $post_array;
			$this->pages[$k]['post_author'] = $this->user_id;
			$this->pages[$k]['post_date']   = date( 'Y-m-d H:i:s', strtotime( $post_array['post_date'] ) );
			if ( isset( $child ) ) {
				$this->pages[$k]['post_parent'] = $child;
			}
			elseif ( isset( $parent ) ) {
				$this->pages[$k]['post_parent'] = $parent;
			}
			$this->pages[$k]['id']          = $this->factory->post->create( $this->pages[$k] );

			if ( ! isset ( $parent ) ) {
				$parent = $this->pages[$k]['id']; // first page added
			}
			elseif ( ! isset( $child ) ) {
				$child = $this->pages[$k]['id']; // second page added
			}
		}
		unset( $parent, $child, $k, $post_array );


		// get post
		//$this->go_to( get_permalink( $post_id ) );
		//		$this->go_to( get_author_posts_url( $user_id ) );
		//get_term_link( $term_id, $taxonomy )

	}


	function tearDown() {
		parent::tearDown();
	}


	/* ******************************** DATA PROVIDERS *************************** */
	public function get_term_data() {
		$data = array_keys( $this->term_base_info );
		foreach ( $data as $k => $key ) {
			$data[$k] = (array) $key;
		}
		return $data;
	}

	public function get_post_data() {
		$data = array_keys( $this->post_base_info );
		foreach ( $data as $k => $key ) {
			$data[$k] = (array) $key;
		}
		return $data;
	}

	public function get_page_data() {
		$data = array_keys( $this->page_base_info );
		foreach ( $data as $k => $key ) {
			$data[$k] = (array) $key;
		}
		return $data;
	}


	/* ******************************** TEST DEFAULT TITLES *************************** */

	public function test_wpseo_replace_default_home_title() {
		$this->go_to( home_url( '/' ) );
		$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-home-wpseo' ); //'%%sitename%% %%page%% %%sep%% %%sitedesc%%'
		$expected = WP_TESTS_TITLE . ' - Just another WordPress site';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}


	/**
	 * @dataProvider get_post_data
	 */
	public function test_wpseo_replace_default_post_title( $key ) {
		$input    = $this->posts[$key];
		$post     = get_post( $input['id'] );
		$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-post' ); //'%%title%% %%page%% %%sep%% %%sitename%%'
		$expected = $input['post_title'] . ' - ' . WP_TESTS_TITLE;
		//$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $post ) );
	}

	/**
	 * @dataProvider get_page_data
	 */
	public function test_wpseo_replace_default_page_title( $key ) {
		$input    = $this->pages[$key];
		$post     = get_post( $input['id'] );
		$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-page' ); //'%%title%% %%page%% %%sep%% %%sitename%%'
		$expected = $input['post_title'] . ' - ' . WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $post ) );
	}


	public function test_wpseo_replace_default_post_archive_title() {
		// '%%pt_plural%% Archive %%page%% %%sep%% %%sitename%%'
		// @todo: add test
		$this->markTestIncomplete();
	}

	/**
	 * @dataProvider get_term_data
	 */
	public function test_wpseo_replace_default_tag_archive_title( $key ) {
		$input = $this->terms[$key];
		if ( $input['taxonomy'] === 'post_tag' ) {
			$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
			$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-tax-post_tag' ); // '%%term_title%% Archives %%page%% %%sep%% %%sitename%%'
			$expected = $input['name'] . ' Archives - ' . WP_TESTS_TITLE;
			$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
		}
	}

	/**
	 * @dataProvider get_term_data
	 */
	public function test_wpseo_replace_default_category_archive_title( $key ) {
		$input = $this->terms[$key];
		if ( $input['taxonomy'] === 'category' ) {
			$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
			$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-tax-category' ); // '%%term_title%% Archives %%page%% %%sep%% %%sitename%%'
			$expected = $input['name'] . ' Archives - ' . WP_TESTS_TITLE;
			$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
		}
	}

	public function test_wpseo_replace_default_date_archive_title() {
		$this->go_to( home_url( '/2000/01/' ) );
		$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-archive-wpseo' ); // '%%date%% %%page%% %%sep%% %%sitename%%'
		$expected = 'January 2000 - ' . WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, array() ) );
	}

	public function test_wpseo_replace_default_author_title() {
		$this->go_to( get_author_posts_url( $this->user_id ) );
		$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-author-wpseo' ); // '%%name%%, Author at %%sitename%% %%page%% ';
		$expected = 'User_Nicename, Author at ' . WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, array() ) );
	}

	public function test_wpseo_replace_default_search_title() {
		$query = add_query_arg( array( 's' => 'search test' ), home_url( '/' ) );
		$this->go_to( $query );
		$this->assertTrue( is_search() );

		$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-search-wpseo' ); // 'You searched for %%searchphrase%% %%page%% %%sep%% %%sitename%%';
		$expected = 'You searched for search test - ' . WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, array() ) );

		//$query = remove_query_arg( 's', $query );
	}

	public function test_wpseo_replace_default_404_title() {
		$this->go_to( '/' . rand_str() );
		//$this->assertTrue( is_404() );
		//$this->assertQueryTrue( 'is_404' );

		$string   = WPSEO_Options::get_default( 'wpseo_titles', 'title-404-wpseo' ); // 'Page Not Found %%sep%% %%sitename%%'
		$expected = 'Page Not Found - ' . WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, array() ) );
	}


	public function test_wpseo_remove_duplicate_seps() {
		$this->go_to( home_url( '/' ) );
		$string   = 'Test %%sep%% %%sep%% %%sep%% %%sitename%%';
		$expected = 'Test - ' . WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/**
	 * @todo [JRF -> Danny] Any idea why this test is not working ? It returns the search phase from the
	 * 'test_wpseo_replace_default_search_title' test while in this case I'm requesting the home_url without
	 * additional query vars. remove_query_arg() does not make a difference
	 */
	public function test_wpseo_remove_nonresolved_vars() {
		$this->go_to( home_url( '/' ) );
		$string   = 'Test %%sep%% %%term404%% %%searchphrase%% %%sep%% %%sitename%%';
		$expected = 'Test - ' . WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );

		$this->markTestIncomplete();
	}



	/* *********************** BASIC VARIABLES ************************** */

	/**
	 * @dataProvider get_term_data
	 * @covers WPSEO_Replace_Vars::retrieve_category
	 */
	public function test_wpseo_replace_category( $key ) {
		$input = $this->terms[$key];
		if ( $input['taxonomy'] === 'category' ) {
			$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
			$string   = '%%category%%';
			$expected = $input['name'];
			$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
		}

		// @todo: add test for cat from post
	}

	/**
	 * @dataProvider get_term_data
	 * @covers WPSEO_Replace_Vars::retrieve_category_description
	 */
	public function test_wpseo_replace_category_description( $key ) {
		$input = $this->terms[$key];
		if ( $input['taxonomy'] === 'category' ) {
			$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
			$string   = '%%category_description%%';
			$expected = $input['description'];
			$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
		}

		// @todo: add test for cat from post
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_date
	 */
	public function test_wpseo_replace_date() {
		// @todo: add test
		$this->markTestIncomplete();
	}


	/**
	 * @dataProvider get_post_data
	 * @covers WPSEO_Replace_Vars::retrieve_excerpt
	 */
	public function test_wpseo_replace_excerpt( $key ) {
		$this->markTestIncomplete();

		$input  = $this->posts[$key];
		$post   = get_post( $input['id'] );
		$string = '%%excerpt%%';
		if ( isset( $input['post_excerpt'] ) ) {
			$expected = $input['post_excerpt'];
		}
		else {
			$expected = $input['post_content']; // @todo adjust value
		}
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $post ) );
	}

	/**
	 * @dataProvider get_post_data
	 * @covers WPSEO_Replace_Vars::retrieve_excerpt_only
	 */
	public function test_wpseo_replace_excerpt_only( $key ) {
		$input  = $this->posts[$key];
		$post   = get_post( $input['id'] );
		$string = '%%excerpt_only%%';
		if ( isset( $input['post_excerpt'] ) ) {
			$expected = $input['post_excerpt'];
		}
		else {
			$expected = '';
		}
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $post ) );
	}

	/**
	 * @dataProvider get_page_data
	 * @covers WPSEO_Replace_Vars::retrieve_parent_title
	 */
	public function test_wpseo_replace_parent_title( $key ) {
		$this->markTestIncomplete();

		$input  = $this->pages[$key];
		$post   = get_post( $input['id'] );
		$string = '%%parent_title%%';
		if ( isset( $input['post_parent'] ) ) {
			$expected = ''; // @todo get title from parent
		}
		else {
			$expected = '';
		}
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $post ) );
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_searchphrase
	 */
	public function test_wpseo_replace_searchphrase() {
		$this->go_to( add_query_arg( array( 's' => 'search test' ), home_url( '/' ) ) );
		$string   = '%%searchphrase%%';
		$expected = 'search test';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_sep
	 */
	public function test_wpseo_replace_sep() {
		$this->go_to( home_url( '/' ) );

		$string   = '%%sep%%';
		$expected = '-';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );

		wp_title( '', false );
		$expected = '-';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );

		// @todo [JRF => whomever] - not sure if this behaviour is correct - I would expect it to return the WP default &raquo;
		wp_title( null, false );
		$expected = '-';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );

		wp_title( '&raquo;', false );
		$expected = '&raquo;';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );

		wp_title( '|', false );
		$expected = '|';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_sitedesc
	 */
	public function test_wpseo_replace_sitedesc() {
		$this->go_to( home_url( '/' ) );
		$string   = '%%sitedesc%%';
		$expected = 'Just another WordPress site';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_sitename
	 */
	public function test_wpseo_replace_sitename() {
		$this->go_to( home_url( '/' ) );
		$string   = '%%sitename%%';
		$expected = WP_TESTS_TITLE;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/**
	 * @dataProvider get_term_data
	 * @covers WPSEO_Replace_Vars::retrieve_tag
	 */
	public function test_wpseo_replace_tag( $key ) {
		$input = $this->terms[$key];
		if ( $input['taxonomy'] === 'post_tag' ) {
			$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
			$string   = '%%tag%%';
			$expected = $input['name'];
			$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
		}

		// @todo: add test for tag from post
	}

	/**
	 * @dataProvider get_term_data
	 * @covers WPSEO_Replace_Vars::retrieve_tag_description
	 */
	public function test_wpseo_replace_tag_description( $key ) {
		$input = $this->terms[$key];
		if ( $input['taxonomy'] === 'post_tag' ) {
			$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
			$string   = '%%tag_description%%';
			$expected = $input['description'];
			$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
		}

		// @todo: add test for tag from post
	}

	/**
	 * @dataProvider get_term_data
	 * @covers WPSEO_Replace_Vars::retrieve_term_description
	 */
	public function test_wpseo_replace_term_description( $key ) {
		$input = $this->terms[$key];
		$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
		$string   = '%%term_description%%';
		$expected = $input['description'];
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/**
	 * @dataProvider get_term_data
	 * @covers WPSEO_Replace_Vars::retrieve_term_title
	 */
	public function test_wpseo_replace_term_title( $key ) {
		$input = $this->terms[$key];
		$this->go_to( get_term_link( $input['id'], $input['taxonomy'] ) );
		$string   = '%%term_title%%';
		$expected = $input['name'];
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_title
	 */
	public function test_wpseo_replace_title() {
		// @todo: add test
		$this->markTestIncomplete();
	}


	/* *********************** ADVANCED VARIABLES ************************** */

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_caption
	 */
	public function test_wpseo_replace_caption() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_cf_custom_field_name
	 */
	public function test_wpseo_replace_cf_custom_field_name() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_ct_custom_tax_name
	 */
	public function test_wpseo_replace_ct_custom_tax_name() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_ct_desc_custom_tax_name
	 */
	public function test_wpseo_replace_ct_desc_custom_tax_name() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_currentdate
	 */
	public function test_wpseo_replace_currentdate() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_currentday
	 */
	public function test_wpseo_replace_currentday() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_currentmonth
	 */
	public function test_wpseo_replace_currentmonth() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_currenttime
	 */
	public function test_wpseo_replace_currenttime() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_currentyear
	 */
	public function test_wpseo_replace_currentyear() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_focuskw
	 */
	public function test_wpseo_replace_focuskw() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_id
	 */
	public function test_wpseo_replace_id() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_modified
	 */
	public function test_wpseo_replace_modified() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_name
	 */
	public function test_wpseo_replace_name() {
		$this->go_to( get_author_posts_url( $this->user_id ) );
		$string   = '%%name%%';
		$expected = 'User_Nicename';
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_page
	 */
	public function test_wpseo_replace_page() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_pagenumber
	 */
	public function test_wpseo_replace_pagenumber() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_pagetotal
	 */
	public function test_wpseo_replace_pagetotal() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_pt_plural
	 */
	public function test_wpseo_replace_pt_plural() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_pt_single
	 */
	public function test_wpseo_replace_pt_single() {
		// @todo: add test
		$this->markTestIncomplete();
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_term404
	 */
	public function test_wpseo_replace_term404() {
		$r = rand_str();
		$this->go_to( '/' . $r );
		//$this->go_to( home_url( '/non-existent-page' ) );
		//$this->assertTrue( is_404() );
		//$this->assertQueryTrue( 'is_404' );

		$string   = '%%term404%%';
		$expected = $r;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, array() ) );
	}

	/*
	 * @covers WPSEO_Replace_Vars::retrieve_userid
	 */
	public function test_wpseo_replace_userid() {
		$this->go_to( get_author_posts_url( $this->user_id ) );
		$string   = '%%userid%%';
		$expected = $this->user_id;
		$this->assertEquals( $expected, wpseo_replace_vars( $string, $GLOBALS['wp_query']->get_queried_object() ) );
	}



	/* *********************** CUSTOM VARIABLES ************************** */

	public function test_wpseo_replace_custom_var() {
		// @todo: add test which includes a call to wpseo_register_var_replacement()
		$this->markTestIncomplete();
	}


	/**
	 * @covers wpseo_replace_vars
	 */
	public function test_wpseo_replace_vars() {

		// create post
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Post_Title',
				'post_content' => 'Post_Content',
				'post_excerpt' => 'Post_Excerpt',
				'post_author'  => $this->user_id,
				'post_date'    => date( 'Y-m-d H:i:s', strtotime( '2000-01-01 2:30:00' ) ),
			)
		);

		// get post
		$post = get_post( $post_id );

		$input    = '%%title%% %%excerpt%% %%date%% %%name%%';
		$expected = 'Post_Title Post_Excerpt '. mysql2date( get_option( 'date_format' ), $post->post_date , true ) . ' User_Nicename';
		$output   = wpseo_replace_vars( $input, (array) $post );
		$this->assertEquals( $expected, $output );

		/*
			TODO
			- Test all Basic Variables
			- Test all Advanced Variables
		 */
	}

}
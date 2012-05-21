<?php
// Hack to set the theme to test. As WP needs to reload once after a new theme
// is set, I can't switch in the setUp, and then switch back to the old theme
// in teardown, so instead I do it like this.
// Another option would be to load the functions.php directly, but that might
// collide with the alread loaded functions.php from the set theme.
switch_theme( 'twentyeleven', 'twentyeleven' );

/**
 * Post Snippets PHPUnit Tests.
 *
 * Unit testing for the Post Snippets WordPress plugin. The test class extends
 * WP_UnitTestCase from Nikolay Bachiyski's WordPress-Tests package.
 *
 * @package		Post Snippets
 * @author		Johan Steen <artstorm at gmail dot com>
 * @since		Post Snippets 1.8.8
 * @see			https://github.com/nb/wordpress-tests
 */
class Post_Snippets_Test extends WP_UnitTestCase {

	// protected	$post_snippets;
	public		$plugin_slug = 'post-snippets';

	/**
	 * setUp runs before each test to create a Fixture.
	 *
	 * The method should have protected access, but because WP_UnitTestCase
	 * doesn't define it that way we'll stick with public.
	 */
	public function setUp() {
		parent::setUp();
		// $this->post_snippets = new Post_Snippets();
		global $post_snippets; $post_snippets = new Post_Snippets();

		$snippets = array();
		array_push($snippets, array(
		    'title' => "TestTmp",
		    'vars' => "",
		    'description' => "",
		    'shortcode' => false,
		    'php' => false,
		    'snippet' => "A test snippet..."));
			update_option('post_snippets_options', $snippets);
	}


	// -------------------------------------------------------------------------
	// Tests
	// -------------------------------------------------------------------------

	public function test_Yo()
	{
		$this->assertTrue(true);
	}

	public function test_Yos()
	{
		$this->assertTrue(true);
	}

	/**
	 * @dataProvider	provider
	 */
	public function test_data_inline($a, $b, $c)
	{
		// var_dump($c);
	}
	public function provider()
	{
		return array(
			array(0, 0, 0),
			array(0, 1, 1),
			array(1, 0, 1),
			array(1, 1, 3)
		);
	}

	public function test_get_post_snippet()
	{
		$test = get_post_snippet('TestTmp');
		$this->assertTrue(is_string($test));
		$this->assertEquals($test, 'A test snippet...');
	}
}

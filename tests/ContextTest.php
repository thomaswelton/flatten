<?php
class ContextTest extends FlattenTests
{
	public function testCanGetCurrentUrl()
	{
		$this->mockRequest('/');
		$this->assertEquals('/', $this->context->getCurrentUrl());
	}

	public function testCanGetCurrentUrlWithQueryString()
	{
		$this->mockRequest('/?q=foo');
		$this->assertEquals('/?q=foo', $this->context->getCurrentUrl());
	}

	public function testCanCheckIfPageMatchesPattern()
	{
		$this->mockRequest('/');
		$this->assertTrue($this->context->matches(array('^/$')));

		$this->mockRequest('/maintainer/foobar');
		$this->assertFalse($this->context->matches(array('^/$')));
		$this->assertTrue($this->context->matches(array('maintainer/.+')));
	}

	public function testCanCheckIfPageShouldBeCached()
	{
		$this->app['config'] = $this->mockConfig(array(
			'flatten::ignore' => array('^/maintainer/anahkiasen', 'admin/.+'),
			'flatten::only'   => array('^/maintainers/.+', 'package/.+'),
		));

		$this->mockRequest('/');
		$this->assertTrue($this->context->shouldCachePage());

		$this->mockRequest('/maintainer/jasonlewis');
		$this->assertTrue($this->context->shouldCachePage());

		$this->mockRequest('/maintainer/anahkiasen');
		$this->assertFalse($this->context->shouldCachePage());

		$this->mockRequest('/admin/maintainers/5/edit');
		$this->assertFalse($this->context->shouldCachePage());

		$this->app['config'] = $this->mockConfig()->shouldReceive('get')->andReturn(null)->mock();
		$this->assertTrue($this->context->shouldCachePage());
	}

	public function testCanUncacheAllPagesWithOnly()
	{
		$this->app['config'] = $this->mockConfig(array(
			'flatten::only'   => array('foobar'),
			'flatten::ignore' => array(),
		));

		$this->mockRequest('/');
		$this->assertFalse($this->context->shouldCachePage());

		$this->mockRequest('/maintainer/jasonlewis');
		$this->assertFalse($this->context->shouldCachePage());

		$this->mockRequest('/maintainer/anahkiasen');
		$this->assertFalse($this->context->shouldCachePage());

		$this->mockRequest('/admin/maintainers/5/edit');
		$this->assertFalse($this->context->shouldCachePage());
	}

	public function testCanUncacheAllPagesWithIgnore()
	{
		$this->app['config'] = $this->mockConfig(array(
			'flatten::only'   => array(),
			'flatten::ignore' => array('.+'),
		));

		$this->mockRequest('/');
		$this->assertFalse($this->context->shouldCachePage());

		$this->mockRequest('/maintainer/jasonlewis');
		$this->assertFalse($this->context->shouldCachePage());

		$this->mockRequest('/maintainer/anahkiasen');
		$this->assertFalse($this->context->shouldCachePage());

		$this->mockRequest('/admin/maintainers/5/edit');
		$this->assertFalse($this->context->shouldCachePage());
	}

	public function testCanCheckIfInAllowedEnvironment()
	{
		$this->app['config'] = $this->mockConfig();
		$this->app['config']->shouldReceive('get')->with('flatten::environments')->andReturn(array('local'));

		$this->assertTrue($this->context->isInAllowedEnvironment());

		$this->app['env'] = 'production';
		$this->assertTrue($this->context->isInAllowedEnvironment());

		$this->app['env'] = 'local';
		$this->assertFalse($this->context->isInAllowedEnvironment());
	}

	public function testCanCheckIfShouldRun()
	{
		$this->app['config'] = $this->mockConfig(array(
			'flatten::environments' => array('local'),
			'flatten::ignore'       => array('^/maintainer/anahkiasen', 'admin/.+'),
			'flatten::only'         => array('^/maintainers/.+', 'package/.+'),
		));

		$this->app['env'] = 'local';
		$this->mockRequest('/maintainer/jasonlewis');
		$this->assertFalse($this->context->shouldRun());

		$this->app['env'] = 'production';
		$this->mockRequest('/maintainer/jasonlewis');
		$this->assertTrue($this->context->shouldRun());
	}

	public function testCanUseBlockers()
	{
		$_GET['foo'] = 'bar';
		$this->app['config'] = $this->mockConfig(array(
			'flatten::blockers' => array($_GET['foo'] === 'bar'),
		));
		$this->assertTrue($this->context->shouldRun());

		$_GET['foo'] = 'baz';
		$this->app['config'] = $this->mockConfig(array(
			'flatten::blockers' => array($_GET['foo'] === 'bar'),
		));
		$this->assertFalse($this->context->shouldRun());
	}
}

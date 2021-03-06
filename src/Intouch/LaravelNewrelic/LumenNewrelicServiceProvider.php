<?php

namespace Intouch\LaravelNewrelic;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;
use Intouch\Newrelic\Newrelic;

class LumenNewrelicServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->configure('newrelic');
		$this->app->singleton(
			'newrelic',
			function ( $app ) {
				return new Newrelic( $app['config']->get( 'newrelic.throw_if_not_installed' ) );
			}
		);

		app('queue')->before(function ($event) {
			app('newrelic')->backgroundJob( true );
			app('newrelic')->startTransaction( ini_get('newrelic.appname') );
			if (app('config')->get( 'newrelic.auto_name_jobs' )) {
				app('newrelic')->nameTransaction( $this->getJobName($event) );
			}
		});

		app('queue')->after(function ($event) {
			app('newrelic')->endTransaction();
		});
	}

	/**
	* Build the job name
	*
	* @return string
	*/
	public function getJobName($event)
	{
		return str_replace(
			[
				'{connection}',
				'{class}',
				'{data}',
				'{args}',
				'{input}',
			],
			[
				$event->connectionName,
				get_class($event->job),
				json_encode($event->data),
				implode(', ', array_keys($event->data)),
				implode(', ', array_values($event->data)),
			],
			$this->app['config']->get( 'newrelic.job_name_provider' )
		);
	}
}

Introduction
============

The Job Queue Bundle is responsible for managing recurring, and non recurring jobs.

It uses php-resque to manage a job queue, (for which various workers process tasks).
These workers should be maintained by supervisord to ensure they don't fail.

This bundle utilizes the BCCResqueBundle, which provides glue code between phpr-esque and Symfony2.

Certain parts of the BCCResque Bundle, have been reimplemented in this Bundle to allow more control, particularly templates/controllers and styling used to view the job queue, and support for viewing the recurring job configuration.

Adding Jobs
============

The majority of jobs that will be added using this bundle, are console commands which are added to the queue to be processed at a later date. There is a genric CommandJob, which is used for adding these jobs. Adding a 'Command Job' can be achieved using the 'jobby' service as follows:

```php
	$container->get('jobby')
		->addCommandJob(
			'your:console:command --plus=any --options or arguments', #this needs to be a valid command
			'nameofqueue', #should be a valid queue name (see 'Named Queues')
			600, # allowed timeout for command (see symfony process component documentation)
			600, # allowed idle timeout for command (see symfony process component documentation)
		)
```

Scheduling Jobs
===============

Rather than scheduling jobs using the crontab, all tasks should be managed in environment specific configuration files (using crontab syntax) which add commands to the job queue on the same schedule as present in the crontab. This allows the addition of new recurring jobs, or changing of timings, without having to modify crontab in multiple environments. It also has the advantage of forcing a common logging/exception notification strategy for all system tasks.

[Sample Recurring Job Configuration](Resources/docs/sample_recurring.yml)

Configuration
===============
This bundle can be configured by adding a list of allowed 'queues' to which jobs can be added. This list of queues is later used to instigate discreete workers when deploying the application (1 worker per queue).

Additionally a 'recurring' job configuration can be added on a per environment basis, to allow for control of various recurring jobs within different environments.

```yml
	markup_job_queue:
	    queues:
	        - named-queue
	        - feeds
	        - system
	        - mail
	        - dataexchange
	        - web-service-outbound
	        - etcetcetc
	    recurring: recurring_jobs.yml # name of file within app/config/
```

Enabling and Montoring Workers
================
A sample supervisord configuration should be included in the app folder, and this configuration should be added as part of the deployment process to ensure that jobs are executed automatically. The basics of the approach are documented in the BCCEResqueBundle.

TODO: Include sample supervisord config and sample capistrano task for deploying supervisord configurations.

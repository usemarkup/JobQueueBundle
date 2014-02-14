The Job Queue Bundle is responsible for managing Recurring, and non recurring jobs.

It uses php-resque to manage a job queue, which is stored in redis, and for which various workers process tasks.
These workers should be maintained by supervisord to ensure they don't fail.

This bundle utilizes the BCCResqueBundle, which provides glue code between phpr-esque and Symfony2.

Certain parts of the BCCResque Bundle, have been reimplemented in this Bundle to allow more control, particularly templates/controllers and styling used to view the job queue.

Adding Jobs
============

The majority of jobs that will be added using this bundle, are console commands which are added to the queue to be processed at a later date. There is a genric CommandJob, which is used for adding these jobs. Adding a 'Command Job' can be achieved using the 'jobby' service as follows:

$container->get('jobby')
	->addCommandJob(
		'your:console:command --plus=any --options or arguments', #this needs to be a valid command
		'nameofqueue', #should be a valid queue name (see 'Named Queues')
		600, # allowed timeout for command (see symfony process component documentation)
		600, # allowed idle timeout for command (see symfony process component documentation)
	)

Scheduling Jobs
===============

Rather than scheduling jobs using the crontab, all tasks should be managed in environment specific configuration files (using crontab syntax) which add commands to the job queue on the same schedule as present in the crontab.
TODO: Include sample 'recurring.yml'

Enabling Workers
================
A sample supervisord configuration should be included in the app folder, and this configuration should be added as part of the deployment process to ensure that jobs are executed automatically.
TODO: Include sample supervisord config and sample capistrano task for deploying supervisord configurations.

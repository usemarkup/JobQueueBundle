Introduction
============

The Job Queue Bundle is responsible for managing recurring, and non recurring jobs.

It uses php-resque to manage a job queue, (for which various workers process tasks).
These workers should be maintained by supervisord to ensure they don't fail.

This bundle utilizes the BCCResqueBundle, which provides glue code between php-resque and Symfony2.

Certain parts of the BCCResque Bundle, have been reimplemented in this Bundle to allow more control, particularly templates/controllers and styling used to view the job queue, and support for viewing the recurring job configuration.

Adding Jobs
============

Jobs are added using a single service. There is a utility method for adding 'command' jobs, which uses the Symfony2 process component to execute console commands. Adding a 'Command Job' can be achieved using the 'jobby' service as follows:

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

Rather than scheduling console commands using the crontab, they should be managed in environment specific configuration files (using crontab syntax) which add commands to the job queue on the same schedule as present in the crontab. This allows the addition of new recurring jobs, or changing of timings, without having to modify crontab in multiple environments. It also has the advantage of forcing a common logging/exception notification strategy for all system tasks.

[Sample Recurring Job Configuration!](Resources/docs/sample_recurring.yml)

Configuration
===============
This bundle can be configured by adding a list of allowed 'queues' to which jobs can be added. This list of queues is later used to instigate discrete workers when deploying the application (1 worker per queue).

Additionally a 'recurring' job configuration can be added on a per environment basis, to allow for control of various recurring jobs within different environments (see 'Scheduling Jobs').

```yml
	markup_job_queue:
	    queues:
	        serverA
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

You may find it useful to tie supervisor and resque into your deployment process.

A command has been written for the purposes of generating these configuration files and copying them to a location where they will be re-read by supervisord. See the command 'WriteSupervisordConfigFileCommand' for more details

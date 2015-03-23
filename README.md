Introduction
============

The Job Queue Bundle is responsible for managing recurring, and non recurring console command jobs.

It uses rabbit-mq to manage a job queue, (for which various workers process tasks).
These workers should be maintained by supervisord to ensure they don't fail.


Scheduling Jobs
---------------

Rather than scheduling console commands using the crontab, they should be managed in an environment specific configuration files (using crontab syntax) which add commands to the job queue on the same schedule as present in the crontab. This allows the addition of new recurring jobs, or changing of timings, without having to modify crontab in multiple environments. It also has the advantage of forcing a common logging/exception notification strategy for all system tasks.


Configuration
~~~~~~~~~~~~~

```yml
	markup_job_queue:
	    recurring: recurring_jobs.yml # name of file within app/config/
```

```yml
	# app/config/recurring_jobs.yml
	- command: your:console:command --and --any --options and arguments
	  schedule: 1-59/2 * * * *
	  topic: topic-of-a-configured-rabbitmq-producer # e.g 'default'
	- command: another:console:command --and --any --options and arguments
	  schedule: * * * * *
	  topic: topic-of-a-configured-rabbitmq-producer
```

Adding Jobs
-----------

Jobs are added using a single service. There is a utility method for adding 'command' jobs, which uses the Symfony2 process component to execute console commands. Adding a 'Command Job' can be achieved using the 'jobby' service as follows:

```php
	$container->get('jobby')
		->addCommandJob(
			'your:console:command --plus=any --options or arguments', #this needs to be a valid command
			'topic', # should be a valid topic name
			600, # allowed timeout for command (see symfony process component documentation)
			600, # allowed idle timeout for command (see symfony process component documentation)
		)
```

Deployment / Enabling and Montoring Workers
================

WIP

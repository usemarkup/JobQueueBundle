Introduction
============

The Job Queue Bundle is responsible for managing recurring, and non recurring console command jobs.

It uses rabbit-mq to manage a job queue, (for which various workers process tasks).
These workers should be maintained by supervisord to ensure they don't fail.


Scheduling Jobs
---------------

Rather than scheduling console commands using the crontab, they should be managed in an environment specific configuration files (using crontab syntax) which add commands to the job queue on the same schedule as present in the crontab. This allows the addition of new recurring jobs, or changing of timings, without having to modify crontab in multiple environments. It also has the advantage of forcing a common logging/exception notification strategy for all system tasks.


Configuration
-------------

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

Once you have configured your recurring schedule you need to add only one console command to your live crontab. This will run a single console command every minute adding any 'due' jobs to RabbitMQ for processing:

```vim
* * * * * /usr/bin/php /your/app/location/current/app/console markup:job_queue:recurring:add --no-debug -e=prod >> /var/log/recurring_jobs.log
```

Adding Jobs
-----------

Jobs are added using a single service. There is a utility method for adding 'command' jobs, which uses the Symfony2 process component to execute console commands. Adding a 'Command Job' can be achieved using the 'jobby' service as follows:

```php
$container->get('jobby')
	->addCommandJob(
		'your:console:command --plus=any --options or arguments', #this needs to be a valid command
		'a_valid_topic', # should be a valid topic name
		600, # allowed timeout for command (see symfony process component documentation)
		600, # allowed idle timeout for command (see symfony process component documentation)
	)
```

For the value of 'topic' a valid consumer and producer need to be set up in the oldsound/rabbiitmq-bundle configuration as follows, without a configuration of this type, processing of the job will fail (this is currently a convention but would be better enforced by allowing this bundle to configure the oldsound bundle directly - PR's welcome):

```yml
producers:
        a_valid_topic:
                connection:       default
                exchange_options: { name: 'a_valid_topic', type: topic }
consumers:
	a_valid_topic:
		connection:       default
		exchange_options: { name: 'a_valid_topic', type: topic }
		queue_options:    { name: 'a_valid_topic' }
		callback:         markup_job_queue.consumer
```

Enabling and Monitoring Workers (via supervisord)
================

To aid with deployment this bundle, a console command has been provided which can be run as part of a deployment. This console command will generate a supervisord file for the purpose of including within your main supervisord.conf file. This will produce a configuration that initiates and watches php 'consumers', providing one consumer per topic:


This console command requires a minimal configuration (one block for each consumer you want to start. By convention these must match the consumers you have already defined (as seen above):

```yml
markup_job_queue:
	topics:
		test:
			consumption_quantity: 10
		a_valid_topic:
			consumption_quantity: 20
```

To write the configuration file:

```bash
app/console markup:job_queue:supervisord_config:write disambiguator
```

The file will be written to /etc/supervisord/conf.d/ by default. This can be amended:
```yml
supervisor_config_path: /path/to/conf file
```
This path needs to be included in your main /etc/supervisord.conf thus:
```conf
[include]
files=/path/to/conf/file/*.conf
```

Deployment
================
To use this as part of a capistrano deployment for example you can write some custom capistrano tasks that:

- Stop consumers
- Rewrite the configuration
- Restart the consumers

The following assumes use of capistrano multistage under capifony 2.X YMMV
```ruby
namespace :supervisor do
    	desc "Supervisor Tasks"
    	task :check_config, :roles => :app do
		stream "cd #{latest_release} && #{php_bin} #{symfony_console} markup:job_queue:recurring:check --env=#{symfony_env}"
	end
	task :write_config, :roles => :worker, :except => { :no_release => true } do
	        stream("cd #{latest_release} && #{php_bin} #{symfony_console} markup:job_queue:supervisord_config:write #{fetch(:stage)} --env=#{symfony_env_prod};")
	end
	task :restart_all, :roles => :app, :except => { :no_release => true } do
		stream "#{try_sudo} supervisorctl stop all #{fetch(:stage)}:*"
		stream "#{try_sudo} supervisorctl update"
		stream "#{try_sudo} supervisorctl start all #{fetch(:stage)}:*"
		capifony_puts_ok
	end
	task :stop_all, :roles => :app, :except => { :no_release => true } do
		# stops all consumers in this group
		stream "#{try_sudo} supervisorctl stop all #{fetch(:stage)}:*"
		capifony_puts_ok
	end
end
```

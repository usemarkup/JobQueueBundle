[![Build Status](https://api.travis-ci.org/usemarkup/JobQueueBundle.svg)](http://travis-ci.org/usemarkup/JobQueueBundle)

Introduction
============

This bundle provides a few features for providing a simple job queue mechanic and scheduling system for symfony console commands.
It uses rabbit-mq to manage a job queue, (for which various workers process tasks). Before proceeding you should read: https://github.com/videlalvaro/RabbitMqBundle.
This bundle assumes the use of 'topic' consumers rather than 'direct' consumers.

These workers should be maintained by supervisord to ensure they don't fail. 

Features
============
- Add console command jobs to RabbitMq to be handled asyncronously
- Add jobs to run at a date in the future
- Log the status of jobs (status, peak memory use, output etc) in redis via uuid option added to all console commands
- Consume Jobs with a PHP consumer or Golang consumer (thanks to ricbra/rabbitmq-cli-consumer)
- Helper command for generating config to manage consumers with Supervisord

Scheduling Jobs
---------------

Rather than scheduling console commands using the crontab, they should be managed in environment specific configuration files (using crontab syntax). This allows the addition of new recurring jobs, or changing of timings, without having to modify crontab in multiple environments. It also has the advantage of forcing a common logging/exception notification strategy for all commands. Examples of these sorts of tasks are polling third party servers for files, sending spooled email or generating reports.


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

Once you have configured your recurring schedule you need to add only one console command to your live crontab.
This will run a single console command every minute adding any 'due' jobs to RabbitMQ for processing:

```vim
* * * * * /usr/bin/php /your/app/location/current/app/console markup:job_queue:recurring:add --no-debug -e=prod >> /var/log/recurring_jobs.log
```

In development instead of installing to the crontab you can run on an interval in the command line if you prefer:

```vim
while true; do app/console markup:job_queue:recurring:add; sleep 60; done
```

For the value of 'topic' a valid consumer and producer need to be set up in the oldsound/rabbitmq-bundle configuration as follows, without a configuration of this type, processing of the job will fail (this is currently a convention but would be better enforced by allowing this bundle to configure the oldsound bundle directly - PR's welcome):
__Due to the way oldsound/rabbitmq-bundle treats certain keys, do not use hypens in producers and consumers.__

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

There are a few console commands that allow you to preview and validate your configured console jobs via the CLI (see /Command)

Adding Jobs
-----------

Jobs can also be added directly. There is a utility method for adding 'command' jobs, which uses the Symfony2 process component to execute console commands. Adding a 'Command Job' can be achieved using the 'jobby' service as follows:

```php
$container->get('jobby')
	->addCommandJob(
		'your:console:command --plus=any --options or arguments', #this needs to be a valid command
		'a_valid_topic', # should be a valid topic name
		600, # allowed timeout for command (see symfony process component documentation)
		600, # allowed idle timeout for command (see symfony process component documentation)
	)
```

You can use this mechanism to break down large import tasks into smaller sections that can be processed asynchronously. Make sure you appropriately escape any user provided parameters to your console commands. Due to the way that console commands are consumed using the Process component, unescaped parameters are a possible security attack vector.

Enabling and Monitoring Workers (via supervisord)
================

To aid with deployment of this bundle, a console command has been provided which can be run as part of a deployment. This console command will generate a supervisord file for the purpose of including within your main supervisord.conf file. This will produce a configuration that initiates and watches php 'consumers', providing one consumer per topic. There are two options for consuming jobs. The default mechanism is to use the PHP consumers provided by oldsound/rabbitmq-bundle, but an alternative mechanism uses the Golang based consumer (ricbra/rabbitmq-cli-consumer). To use the Golang variant, provide a configuration for the `cli_consumer` node.

```yml
markup_job_queue:
	cli_consumer:
	    enabled: true
```

This console command requires a minimal configuration (one block for each consumer you want to start). By convention these must match the consumers you have already defined (as seen above). __Due to the way oldsound/rabbitmq-bundle treats certain keys, do not use hypens in your topic names.__:

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
markup_job_queue:
	supervisor_config_path: /path/to/conf/file/
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

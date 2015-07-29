# Core Bundle

## Features
*  **AbstractEntity**: base properties and behaviours (*doctrine/orm*, *knplabs/doctrine-behaviors*)
* Support for **CronTasks**: see [Creating automated, interval-based "cron" tasks in Symfony2](https://inuits.eu/blog/creating-automated-interval-based-cron-tasks-symfony2)  (*symfony/framework-bundle*, *symfony/console*, *symfony/doctrine-bridge*)
* **BeforeControllerListener**: InitializableControllerInterface: Controller->initialize(Request $request, SecurityContextInterface $security_context) (*symfony/http-foundation*, *symfony/security*)
* Service **OGDBWrapper** and model class for **GeoCode** (*symfony/framework-bundle*, *symfony/dependency-injection*)
* FormTypes **DatePickerType** and **TimePickerType** (*symfony/form*, *symfony/options-resolver*, *sonata-project/core-bundle*)
<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/TaskRepository.php';

require_once __DIR__ . '/Core/Router.php';
require_once __DIR__ . '/Core/View.php';

require_once __DIR__ . '/Adapters/DatabaseAdapterInterface.php';
require_once __DIR__ . '/Adapters/SqliteDatabaseAdapter.php';
require_once __DIR__ . '/Adapters/MySqlDatabaseAdapter.php';

require_once __DIR__ . '/Models/TaskModel.php';
require_once __DIR__ . '/Models/UserModel.php';
require_once __DIR__ . '/Models/ProjectModel.php';

require_once __DIR__ . '/Factories/ModelFactory.php';

require_once __DIR__ . '/Strategies/TaskTimeAnalyticsStrategyInterface.php';
require_once __DIR__ . '/Strategies/SimpleTaskTimeAnalyticsStrategy.php';
require_once __DIR__ . '/Strategies/StatusGroupedTaskTimeAnalyticsStrategy.php';

require_once __DIR__ . '/Repositories/TaskWriterInterface.php';
require_once __DIR__ . '/Repositories/TaskWriterRepository.php';
require_once __DIR__ . '/Repositories/LoggingTaskWriterDecorator.php';

require_once __DIR__ . '/Controllers/AnalyticsController.php';
require_once __DIR__ . '/Controllers/TaskMvcController.php';
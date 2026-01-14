<?php

namespace AyupCreative\EventLog\Tests\Unit;

use AyupCreative\EventLog\Features\LogsEvents;
use AyupCreative\EventLog\Tests\UnitTestCase;
use Illuminate\Database\Eloquent\Model;

class LogsEventsTraitTest extends UnitTestCase
{
    public function test_it_provides_default_event_namespace(): void
    {
        $model = new DefaultNamespaceModel();
        $this->assertSame('default_namespace_model', $model->eventNamespace());
    }

    public function test_it_can_override_event_namespace(): void
    {
        $model = new TestModelWithOverride();
        $this->assertSame('custom.namespace', $model->eventNamespace());
    }

    public function test_it_defaults_to_log_all_events(): void
    {
        $model = new class extends Model {
            use LogsEvents;
        };
        $this->assertTrue($model->shouldLogEvent('created'));
    }

    public function test_it_has_empty_default_relations(): void
    {
        $model = new class extends Model {
            use LogsEvents;
        };
        $this->assertSame([], $model->eventRelations('created'));
    }

    public function test_it_can_return_custom_relations(): void
    {
        $relatedModel = new class extends Model {};
        $model = new class($relatedModel) extends Model {
            use LogsEvents;
            protected $related;
            public function __construct($related = null) {
                parent::__construct();
                $this->related = $related;
            }
            public function eventRelations(string $event): array {
                return [$this->related];
            }
        };
        $this->assertSame([$relatedModel], $model->eventRelations('created'));
    }

    public function test_it_can_conditionally_log_events(): void
    {
        $model = new class extends Model {
            use LogsEvents;
            public function shouldLogEvent(string $event): bool {
                return $event === 'allowed';
            }
        };
        $this->assertTrue($model->shouldLogEvent('allowed'));
        $this->assertFalse($model->shouldLogEvent('denied'));
    }
}

class TestModelWithOverride extends Model {
    use LogsEvents;
    public function eventNamespace(): string {
        return 'custom.namespace';
    }
}

class DefaultNamespaceModel extends Model {
    use LogsEvents;
}

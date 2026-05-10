<?php

declare(strict_types=1);

return <<<'PROMPT'
Analysiere folgendes Support-Ticket:

**Titel:** {{ ticketTitle }}

**Kontakt:** {{ contactName }} ({{ contactEmail }})

**Beschreibung:**
{{ ticketBody }}

{{ attachmentInfo }}
PROMPT;

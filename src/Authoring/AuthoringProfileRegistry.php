<?php

declare(strict_types=1);

namespace Simai\Docara\Authoring;

final readonly class AuthoringProfileRegistry
{
    public const IDS = ['landing', 'article', 'tutorial', 'how_to', 'reference', 'explanation'];

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return [
            'landing' => $this->profile('Present an entry point and guide the reader to an action.', ['general'], ['one_h1', 'introduction', 'multiple_sections', 'action'], ['audience_fit', 'value_proposition', 'action_clarity']),
            'article' => $this->profile('Explain one subject as a coherent article.', ['general'], ['one_h1', 'introduction', 'main_material'], ['focus', 'clarity', 'completeness']),
            'tutorial' => $this->profile('Teach a complete outcome through sequential practice.', ['learner'], ['one_h1', 'ordered_steps'], ['expected_outcome', 'prerequisites', 'verification_quality']),
            'how_to' => $this->profile('Help complete one practical task.', ['practitioner'], ['one_h1', 'ordered_steps'], ['task_boundary', 'step_safety', 'result_confirmation']),
            'reference' => $this->profile('Provide an exact surface for lookup.', ['practitioner'], ['one_h1', 'introduction', 'structured_reference'], ['terminology', 'parameter_accuracy', 'coverage']),
            'explanation' => $this->profile('Explain context, rationale and constraints.', ['general'], ['one_h1', 'introduction', 'main_material'], ['rationale', 'limitations', 'related_materials']),
        ];
    }

    /** @param list<string> $audience @param list<string> $signals @param list<string> $checklist */
    private function profile(string $purpose, array $audience, array $signals, array $checklist): array
    {
        return ['purpose' => $purpose, 'expected_audience' => $audience, 'structural_signals' => $signals, 'editorial_checklist' => $checklist];
    }
}

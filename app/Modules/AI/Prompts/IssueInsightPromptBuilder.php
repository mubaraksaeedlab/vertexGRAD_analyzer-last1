<?php

namespace App\Modules\AI\Prompts;

use App\Modules\Analysis\Models\Issue;

class IssueInsightPromptBuilder
{
    public function build(Issue $issue): array
    {
        $systemPrompt = <<<TEXT
You are a senior static analysis reviewer and code quality consultant.

Your task is to explain a detected issue in a professional, concise, and technically meaningful way.

Rules:
- Explain what the issue means.
- Explain its likely impact.
- Explain its likely root cause.
- Suggest a practical fix.
- Do not invent unsupported technical details.
- Stay grounded in the issue data only.
TEXT;

        $userPrompt = [
            'issue_id' => $issue->id,
            'rule_code' => $issue->rule_code,
            'category' => $issue->category,
            'severity' => $issue->severity,
            'language' => $issue->language,
            'title' => $issue->title,
            'description' => $issue->description,
            'recommendation' => $issue->recommendation,
            'snippet' => $issue->snippet,
            'confidence' => $issue->confidence,
            'metadata' => $issue->metadata,
        ];

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
        ];
    }
}
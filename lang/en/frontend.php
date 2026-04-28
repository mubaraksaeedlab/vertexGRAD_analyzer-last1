<?php

return [

    'analyzer' => [
        'home' => [
            'meta' => [
                'title' => 'VertexGrad Analyzer',
            ],

            'hero' => [
                'title_line_1' => 'Turn Your Academic Projects Into',
                'title_line_2' => 'Real Opportunities',
                'description' => 'Smart analysis, structured review, and a clear presentation for projects ready to grow.',
                'start_analysis' => 'Start Analysis',
                'explore_platform' => 'Explore Platform',
            ],

            'features' => [
                'title' => 'Powerful Platform Features',
                'subtitle' => 'Everything you need to analyze, improve, and present your academic projects.',
                'items' => [
                    'smart_analysis' => [
                        'title' => '⚡ Smart Analysis',
                        'description' => 'Intelligent multi-language code scanning with advanced detection capabilities.',
                    ],
                    'intelligent_scoring' => [
                        'title' => '📊 Intelligent Scoring',
                        'description' => 'A scoring system based on categories such as security, quality, and performance.',
                    ],
                    'supervisor_review' => [
                        'title' => '🧠 Supervisor Review',
                        'description' => 'A multi-level evaluation workflow with specialized insights and notes.',
                    ],
                    'project_pipeline' => [
                        'title' => '📁 Project Pipeline',
                        'description' => 'An organized flow from submission to analysis, approval, and publication.',
                    ],
                    'smart_reports' => [
                        'title' => '📄 Smart Reports',
                        'description' => 'Generate professional PDF reports ready for presentation and review.',
                    ],
                    'investor_ready' => [
                        'title' => '💼 Investor Ready',
                        'description' => 'Transform academic projects into realistic investment opportunities.',
                    ],
                ],
            ],

            'process' => [
                'title' => 'How the System Works',
                'steps' => [
                    'upload' => [
                        'title' => 'Upload Project',
                        'description' => 'Submit your academic project securely.',
                    ],
                    'analyze' => [
                        'title' => 'Analysis',
                        'description' => 'The system scans and evaluates your project.',
                    ],
                    'review' => [
                        'title' => 'Review',
                        'description' => 'Supervisors verify and improve the results.',
                    ],
                ],
            ],

            'cta' => [
                'title' => 'Ready to improve your project?',
                'description' => 'Start analyzing your project now and discover its full potential.',
                'button' => 'Start Now',
            ],
        ],
    ],

    'project_page' => [
        'hero_badge' => 'Project Tracking',
        'hero_description' => 'Review preparation status, analysis progress, discovered files, and final results from one organized page.',

        'status' => [
            'completed' => 'Completed',
            'running' => 'Running',
            'queued' => 'Queued',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'unknown' => 'Unknown',
        ],

        'upload_status' => [
            'queued' => 'Queued in background',
            'extracting' => 'Extracting archive',
            'discovering_files' => 'Discovering project files',
            'prepared' => 'Project is ready',
            'failed' => 'Preparation failed',
            'preparing' => 'Preparing project',
        ],

        'preparation_message' => [
            'queued' => 'Your project was uploaded successfully and is waiting in the background queue.',
            'extracting' => 'Your archive is currently being extracted.',
            'discovering_files' => 'Project files are being discovered and linked now.',
            'prepared' => 'Your project files are ready. You can start the analysis now.',
            'failed' => 'Project preparation failed.',
            'preparing' => 'Your project is being prepared for analysis.',
        ],

        'overview' => [
            'title' => 'Project Overview',
            'description' => 'General information and current analysis state.',
            'project_name' => 'Project Name',
            'status' => 'Status',
            'preparation_stage' => 'Preparation Stage',
            'primary_language' => 'Primary Language',
            'description_label' => 'Description',
            'not_specified_yet' => 'Not specified yet',
            'no_description' => 'No description provided.',
        ],

        'actions' => [
            'run_analysis' => 'Run Analysis',
            'analysis_running' => 'Analysis Running...',
            'preparing_files' => 'Preparing Files...',
            'run_analysis_again' => 'Run Analysis Again',
            'preparation_failed' => 'Preparation Failed',
            'waiting' => 'Waiting...',
            'starting_analysis' => 'Starting Analysis...',
            'creating_analysis_run' => 'Creating analysis run...',
            'preparing_session' => 'Preparing analysis session...',
        ],

        'submission' => [
            'title' => 'Submission Details',
            'description' => 'Submission metadata and uploaded archive information.',
            'student_name' => 'Student Name',
            'student_email' => 'Student Email',
            'original_archive' => 'Original Archive',
            'stored_archive_path' => 'Stored Archive Path',
            'discovered_files' => 'Discovered Files',
            'created_at' => 'Created At',
        ],

        'progress' => [
            'title' => 'Analysis Progress',
            'description' => 'Track scan progress, processed files, current file, and grouped run activity in real time.',
            'waiting' => 'Waiting...',
            'completed' => 'Completed',
            'processing' => 'Processing',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'current_file' => 'Current File',
            'live_updates' => 'Live updates every 0.7 second',
            'details_title' => 'Analyzed Files Details',
            'details_hint' => 'Live grouped file activity will appear here.',
            'waiting_data' => 'Waiting for analysis data...',
            'no_files_progress' => 'No file progress available yet.',
            'live_grouped_activity' => 'Live grouped file activity is updating in real time.',
            'analysis_complete_details' => 'Analysis is complete. Open this section only if you want to review file-level details.',
            'project_root' => 'Project root',
            'files_processed' => 'files processed',
            'analysis_completed_success' => 'Analysis completed successfully',
            'analysis_finished_failures' => 'Analysis finished with failures',
            'no_analysis_run' => 'No analysis run is linked to this project yet.',
            'invalid_status_response' => 'Status endpoint returned invalid JSON.',
            'failed_load_status' => 'Failed to load analysis status.',
            'network_error_status' => 'Could not contact the analysis status endpoint.',
            'could_not_start_analysis' => 'Could not start analysis.',
            'network_error_start' => 'Failed to start analysis due to a network error.',
            'start_analysis_invalid_response' => 'The server returned an invalid response while starting analysis.',
            'items' => 'items',
            'item' => 'item',
            'files' => 'files',
            'file' => 'file',
        ],

        'stats' => [
            'overall_score' => 'Overall Score',
            'grade' => 'Grade',
            'issues_found' => 'Issues Found',
            'report' => 'Report',
            'open_report' => 'Open Report',
        ],

        'files' => [
            'title' => 'Discovered Files',
            'description' => 'Professional file explorer view with searchable tree and collapsible folders.',
            'search_placeholder' => 'Search file name, extension, language, or path...',
            'expand_all' => 'Expand All',
            'collapse_all' => 'Collapse All',
            'files_indexed' => 'files indexed',
            'showing_all' => 'Showing all files',
            'showing_matching_single' => 'Showing :count matching file',
            'showing_matching_plural' => 'Showing :count matching files',
            'no_files_display' => 'No files to display yet.',
            'no_extracted_files' => 'No extracted files are linked to this project yet.',
            'unknown_file' => 'Unknown file',
            'unknown' => 'unknown',
            'other' => 'other',
        ],
    ],

    'project_details' => [
        'page_title_fallback' => 'Detailed Report',
        'details_report' => 'Detailed Report',
        'details_subtitle' => 'A full detailed view of all analysis results including file, line, description, and recommended fix.',

        'back' => 'Back',
        'back_to_summary' => 'Back to Executive Summary',
        'download_pdf' => 'Download PDF Report',
        'back_to_main_platform' => 'Back to Main Platform',

        'search' => 'Search',
        'search_placeholder' => 'Search by title, file, rule, or description...',

        'filters' => [
            'all' => 'All',
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            'info' => 'Info',
        ],

        'summary' => [
            'issues_found' => 'Issues Found',
            'files_affected' => 'Affected Files',
            'primary_language' => 'Primary Language',
            'risk_level' => 'Risk Level',
        ],

        'labels' => [
            'issue_details' => 'Issue Details',
            'technical_description' => 'Technical Description',
            'student_explanation' => 'Student-Friendly Explanation',
            'recommended_fix' => 'Recommended Fix',
            'file' => 'File',
            'line' => 'Line',
            'rule' => 'Rule',
            'severity' => 'Severity',
            'code_snippet' => 'Code Snippet',
            'copy_code' => 'Copy Code',
            'copied' => 'Copied',
            'unknown_file' => 'Unknown file',
            'project_report' => 'Project Report',
            'not_specified' => 'Not specified',
        ],

        'states' => [
            'no_snippet' => 'No code snippet is stored for this issue.',
            'no_results' => 'No matching results.',
            'no_results_desc' => 'Try changing the search or filter to display results.',
            'untitled_finding' => 'Untitled finding',
            'no_description' => 'No description available.',
            'generic_fix' => 'Review this finding and apply a safer implementation.',
        ],

        'ai_chat' => [
            'title' => 'Ask AI About This Report',
            'subtitle' => 'Ask a question about this report and the AI will answer based on the current analysis results.',
            'placeholder' => 'Example: What is the most serious issue in my report? or What should I fix first?',
            'button' => 'Ask AI',
            'loading' => 'Thinking...',
            'empty' => 'The AI answer will appear here.',
            'error' => 'Failed to get AI response.',
            'answer' => 'AI Answer',
            'enter_question' => 'Please enter your question first.',
        ],

        'student_explanations' => [
            'critical' => 'This is a critical issue that may compromise or break the system and should be fixed immediately.',
            'high' => 'This is a high-risk issue that may affect system security or stability if left unresolved.',
            'medium' => 'This is a moderate issue that may lead to future problems or lower code quality over time.',
            'low' => 'This is a minor note that helps improve the quality and structure of the code.',
            'default' => 'This is a general note that helps improve the project.',
        ],
    ],

    'project_pdf' => [
        'title_fallback' => 'Analysis Report #:id',
        'brand' => 'VertexGrad Analyzer',
        'project' => 'Project',
        'report_id' => 'Report ID',
        'generated' => 'Generated',

        'project_overview' => 'Project Overview',
        'project_id' => 'Project ID',
        'primary_language' => 'Primary Language',
        'scan_status' => 'Scan Status',
        'analysis_run_id' => 'Analysis Run ID',
        'files_processed' => 'Files Processed',
        'issues_found' => 'Issues Found',

        'score_summary' => 'Score Summary',
        'overall_score' => 'Overall Score',
        'grade' => 'Grade',
        'security_score' => 'Security Score',
        'quality_score' => 'Quality Score',

        'severity_breakdown' => 'Severity Breakdown',
        'critical' => 'Critical',
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
        'info' => 'Info',

        'detected_issues' => 'Detected Issues',
        'rule' => 'Rule',
        'severity' => 'Severity',
        'language' => 'Language',
        'line' => 'Line',
        'details' => 'Details',
        'recommendation' => 'Recommendation',
        'path' => 'Path',
        'untitled_issue' => 'Untitled Issue',
        'no_issues' => 'No issues found.',
        'unknown_project' => 'Unknown Project',

        'footer' => 'Generated by VertexGrad Analyzer — Professional code analysis and reporting platform.',
    ],

    'executive_report' => [
        'title_fallback' => 'Executive Project Report',
        'executive_report' => 'Executive Report',
        'executive_subtitle' => 'A professional high-level summary showing project status, major issues, and the overall decision before opening the full detailed analysis.',

        'statuses' => [
            'completed' => 'Completed',
            'failed' => 'Failed',
            'running' => 'Running',
            'processing' => 'Processing',
            'queued' => 'Queued',
        ],

        'risk' => 'Risk Level',
        'confidence' => 'Confidence',
        'readiness' => 'Project Readiness',
        'overall_score' => 'Overall Score',
        'grade' => 'Grade',
        'issues_found' => 'Issues Found',
        'primary_language' => 'Primary Language',
        'analyzed_at' => 'Analyzed At',
        'files_processed' => 'Files Processed',
        'files_discovered' => 'Files Discovered',

        'back' => 'Back',
        'view_details' => 'View Full Detailed Analysis',
        'download_pdf' => 'Download PDF',

        'executive_metrics' => 'Executive Metrics',
        'executive_metrics_desc' => 'A concise executive overview that helps the student or reviewer understand the current state quickly.',

        'severity_distribution' => 'Severity Distribution',
        'severity_distribution_desc' => 'Shows how the issues are distributed by severity level.',

        'ai_summary' => 'AI Summary',
        'ai_summary_desc' => 'The main conclusion that should be understood first before opening the detailed report.',

        'top_findings' => 'Top Findings',
        'top_findings_desc' => 'The top three issues that should be reviewed first.',

        'student_explanation' => 'Student-Friendly Explanation',
        'technical_description' => 'Technical Description',
        'recommended_fix' => 'Recommended Fix',
        'main_recommendation' => 'Main Recommendation',
        'decision' => 'System Decision',
        'line' => 'Line',
        'total_findings' => 'Total Findings',

        'severity_labels' => [
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            'info' => 'Info',
        ],

        'no_major_findings' => 'No Major Findings',
        'no_major_findings_desc' => 'No important issues are currently displayed in this report.',

        'no_ai_summary' => 'No AI summary is currently available for this report.',

        'default_decision' => 'The project requires review before moving to the next stage.',
        'default_action' => 'Review the report carefully and start by fixing the highest-severity issues first.',

        'ready' => 'Ready',
        'moderate_readiness' => 'Moderate Readiness',
        'needs_fixes' => 'Needs Fixes',

        'security_findings' => 'Security Findings',
        'high_severity' => 'High Severity',
        'confidence_desc' => 'Reflects how strong and consistent the current analysis signals are.',

        'project_report' => 'Project Report',
        'not_specified' => 'Not specified',
        'unknown_file' => 'Unknown file',
        'untitled_finding' => 'Untitled finding',
        'no_description' => 'No description available.',
        'generic_fix' => 'Review this finding and apply a safer implementation.',

        'student_explanations' => [
            'critical' => 'This is a critical issue that may compromise or break the system and should be fixed immediately.',
            'high' => 'This is a high-risk issue that may affect system security or stability if left unresolved.',
            'medium' => 'This is a moderate issue that may lead to future problems or lower code quality over time.',
            'low' => 'This is a minor note that helps improve the quality and structure of the code.',
            'default' => 'This is a general note that helps improve the project.',
        ],
    ],

    'submit_page' => [
        'meta' => [
            'title' => 'Submit Project | VertexGrad Analyzer',
        ],

        'hero' => [
            'badge' => 'Smart Submission Flow',
            'title' => 'Choose Language, Prepare Folders, Upload Cleanly',
            'description' => 'Select your project language, review the required folders, then upload only the source package needed for analysis.',
            'start_submission' => 'Start Submission',
            'view_requirements' => 'View Requirements',
        ],

        'guide' => [
            'eyebrow' => '01 · Project Language',
            'title' => 'Choose the main language',
            'description' => 'Once selected, the platform shows the folders and files that should be included in the uploaded package.',
            'required_folders_title' => 'Required folders for analysis',
            'required_folders_description' => 'Upload only the core source folders required for a meaningful review.',
        ],

        'upload' => [
            'eyebrow' => '02 · Upload Package',
            'title' => 'Submit your analysis package',
            'description' => 'Keep the package focused on the listed folders only. The upload flow and analysis logic remain exactly as connected in your system.',
            'package_label' => 'Analysis Package (ZIP / RAR)',
            'source_package_title' => 'Upload your source package',
            'source_package_hint' => 'Only the listed folders should be included.',
            'choose_file' => 'Choose File',
            'no_file_selected' => 'No analysis package selected yet.',
            'selected_package' => 'Selected package: :name (:size)',
            'recommended_format' => 'Recommended format: ZIP or RAR. Keep the package clean and focused on required source folders only.',
            'submit_note' => 'The page will stay active and show temporary upload progress until the transfer finishes and the system redirects automatically.',
            'submit_button' => 'Upload Analysis Package',
            'uploading_button' => 'Uploading...',
        ],

        'integration' => [
            'imported_title' => 'Project data imported from VertexGrad Platform',
            'imported_description' => 'Project data was filled automatically from the main platform, so it cannot be edited here.',
            'privacy_title' => 'Source-focused upload',
            'privacy_description' => 'Upload only the essential source files required for analysis. No unnecessary folders or environments are needed.',
        ],

        'form' => [
            'project_name' => 'Project Name',
            'student_name' => 'Student Name',
            'student_email' => 'Student Email',
            'primary_language' => 'Primary Language',
            'auto_not_specified' => 'Auto / Not specified',
            'project_description' => 'Project Description',
            'project_description_help' => 'Briefly describe the purpose of the project and any special notes useful for the reviewer.',
        ],

        'helpers' => [
            'step_1_title' => '1. Choose Language',
            'step_1_text' => 'Select the main language so the platform can show the exact folders needed.',
            'step_2_title' => '2. Prepare Package',
            'step_2_text' => 'Compress only the required source folders into a clean ZIP or RAR package.',
            'step_3_title' => '3. Upload and Continue',
            'step_3_text' => 'The current upload flow, progress display, and redirect remain fully connected to your system.',
        ],

        'overlay' => [
            'title' => 'Uploading your analysis package...',
            'message' => 'Please wait while the source package is being uploaded and prepared for analysis.',
            'initializing' => 'Initializing secure upload...',
            'progress_label' => 'Upload progress',
            'extra' => 'Do not close this page while the upload is in progress.',
            'uploading_source' => 'Uploading source package...',
            'transfer_progress' => 'Transfer in progress...',
            'finalizing' => 'Finalizing package upload...',
            'almost_done' => 'Almost done...',
            'complete_redirecting' => 'Upload complete. Redirecting...',
            'success_message' => 'The analysis package was uploaded successfully. The system is redirecting you now.',
            'success_extra' => 'Your project page will open automatically.',
        ],

        'errors' => [
            'choose_package_first' => 'Please choose a ZIP or RAR analysis package first.',
            'invalid_file_type' => 'Invalid file type. Please upload a ZIP or RAR package.',
            'non_json_response' => 'The server returned a non-JSON response. Please check the backend response format.',
            'unexpected_server_response' => 'Unexpected server response. Please try again.',
            'upload_failed' => 'Upload failed. Please try again.',
            'network_error' => 'Network error while uploading the file. Please try again.',
        ],

        'languages' => [
            'php' => [
                'title' => 'PHP / Laravel',
                'description' => 'Application logic, routes, views, config, and database structure.',
                'required_description' => 'Upload only the folders containing your application logic, routes, views, configuration, and database structure.',
            ],
            'javascript' => [
                'title' => 'JavaScript',
                'description' => 'Frontend and source files without dependencies or generated assets.',
                'required_description' => 'Upload the main application source and project definition files only.',
            ],
            'typescript' => [
                'title' => 'TypeScript',
                'description' => 'Typed source files and main project definition files.',
                'required_description' => 'Include the typed source code and main project configuration files.',
            ],
            'python' => [
                'title' => 'Python',
                'description' => 'Python modules, app folders, and dependency definition files.',
                'required_description' => 'Include Python source modules and dependency definition files.',
            ],
            'java' => [
                'title' => 'Java',
                'description' => 'Main source packages and build configuration only.',
                'required_description' => 'Focus on Java source packages and build configuration files.',
            ],
            'c' => [
                'title' => 'C',
                'description' => 'Core source files, headers, and build scripts.',
                'required_description' => 'Upload only C source files, header files, and the main build instructions.',
            ],
            'cpp' => [
                'title' => 'C++',
                'description' => 'Source files, headers, and project configuration files.',
                'required_description' => 'Upload the core C++ source files, headers, and project build files.',
            ],
            'csharp' => [
                'title' => 'C#',
                'description' => 'Solution source, project files, and application logic only.',
                'required_description' => 'Include project source code and the main solution/project files.',
            ],
            'go' => [
                'title' => 'Go',
                'description' => 'Go modules, cmd, internal, and package source folders.',
                'required_description' => 'Include Go modules and the core application folders only.',
            ],
            'dart' => [
                'title' => 'Dart / Flutter',
                'description' => 'lib source, project config, and mobile app structure.',
                'required_description' => 'Upload Flutter/Dart application source and the main project configuration files.',
            ],
        ],
    ],

    'layout' => [
        'analyzer_platform' => 'Analyzer Platform',
        'home' => 'Home',
        'upload' => 'Upload',
        'project' => 'Project',
        'get_started' => 'Get Started',
        'footer_description' => 'Smart review experience for academic innovation.',
    ],

];
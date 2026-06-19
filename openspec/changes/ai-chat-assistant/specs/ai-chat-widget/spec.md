# ai-chat-widget Specification

## Purpose

Floating AI chat widget embedded in the main application layout. Provides users a natural-language interface to query financial/operational data, visible on every page.

## Requirements

### Requirement: Floating Chat Button

The system MUST render a floating action button in the bottom-right corner of every page that includes the application layout.

#### Scenario: Button visible on page load

- GIVEN a user is authenticated and viewing any application page
- WHEN the page finishes loading
- THEN a circular floating button with the Material Symbols `chat` icon MUST be visible
- AND the button MUST be positioned fixed at bottom-right with z-index 50

#### Scenario: Button visible in dark mode

- GIVEN the user has dark mode enabled
- WHEN the floating button is rendered
- THEN the button MUST use Tailwind `dark:` variants for styling
- AND the icon MUST remain legible against the dark background

### Requirement: Chat Panel Toggle

The system SHALL toggle a slide-in chat panel when the user clicks the floating button.

#### Scenario: Open panel

- GIVEN the chat panel is closed
- WHEN the user clicks the floating chat button
- THEN a chat panel MUST slide in from the bottom-right edge
- AND the panel MUST be 380px wide with a maximum height of 500px

#### Scenario: Close panel

- GIVEN the chat panel is open
- WHEN the user clicks the floating chat button again (or a close icon inside the panel)
- THEN the panel MUST slide out of view

#### Scenario: Close on Escape key

- GIVEN the chat panel is open
- WHEN the user presses the Escape key
- THEN the panel MUST close

### Requirement: Message Display

The system MUST display a scrollable conversation history showing user questions and AI responses.

#### Scenario: User sends a question

- GIVEN the chat panel is open
- WHEN the user types a question and submits it
- THEN the question MUST appear as a right-aligned message bubble in the conversation area
- AND the conversation area MUST auto-scroll to the bottom

#### Scenario: AI response arrives

- GIVEN a user question has been submitted
- WHEN the AI response is received from the API
- THEN the response MUST appear as a left-aligned message bubble below the question
- AND the conversation area MUST auto-scroll to the bottom

### Requirement: Loading State

The system SHALL indicate when a response is being generated.

#### Scenario: Waiting for response

- GIVEN the user has submitted a question
- WHEN the API request is in flight
- THEN the input field MUST be disabled
- AND a loading indicator (Material Symbols `progress_activity` spinning) MUST appear in the message area
- AND the send button MUST be hidden or replaced by the loading indicator

#### Scenario: Response received

- GIVEN a loading indicator is visible
- WHEN the API response completes (success or error)
- THEN the loading indicator MUST be removed
- AND the input field MUST be re-enabled

### Requirement: Input Field

The system SHALL provide a text input with a send action at the bottom of the chat panel.

#### Scenario: Submit via click

- GIVEN the chat panel is open and input is not disabled
- WHEN the user types text and clicks the send button
- THEN the message MUST be submitted to the chat API endpoint

#### Scenario: Submit via Enter key

- GIVEN the chat panel is open and input is not disabled
- WHEN the user types text and presses Enter
- THEN the message MUST be submitted to the chat API endpoint

#### Scenario: Empty input blocked

- GIVEN the chat panel is open
- WHEN the user clicks send or presses Enter with an empty or whitespace-only input
- THEN no request MUST be sent
- AND the input field MUST retain focus

#### Scenario: Disabled during loading

- GIVEN a chat request is in flight
- WHEN the user attempts to type or submit
- THEN the input field MUST NOT accept input
- AND the send button MUST NOT trigger a new request

### Requirement: Error Display

The system SHALL display user-facing error messages when the API fails.

#### Scenario: Network error

- GIVEN a request has been sent to the chat API
- WHEN the request fails due to a network error
- THEN an error message in Spanish MUST appear in the conversation area
- AND the error message MUST include a retry suggestion

#### Scenario: Server error

- GIVEN a request has been sent to the chat API
- WHEN the API returns a 5xx status code
- THEN an error message in Spanish MUST appear in the conversation area
- AND the message MUST indicate the service is temporarily unavailable

#### Scenario: Rate limited

- GIVEN a request has been sent to the chat API
- WHEN the API returns a 429 Too Many Requests
- THEN an error message in Spanish MUST appear indicating the limit has been reached
- AND the message MUST include when the user can try again

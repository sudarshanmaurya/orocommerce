<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async\MessageFilter;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Component\MessageQueue\Client\Message;

/**
 * Removes duplicated messages for a specific product visibility management related topic.
 */
class ReindexMessageFilter implements MessageFilterInterface
{
    /** @var string */
    private $topic;

    /**
     * @param string $topic
     */
    public function __construct(string $topic)
    {
        $this->topic = $topic;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        if (!$buffer->hasMessagesForTopic($this->topic)) {
            return;
        }

        $this->aggregateMessages($buffer);
    }

    /**
     * @param Message|array|string $message
     *
     * @return array
     */
    private function getMessageData($message): array
    {
        if (is_string($message)) {
            return [];
        }

        return $message instanceof Message ? $message->getBody() : $message;
    }

    /**
     * Aggregates messages from the same topic into one message.
     *
     * @param MessageBuffer $buffer
     */
    private function aggregateMessages(MessageBuffer $buffer): void
    {
        $firstMessagesIds = [];
        $entityIdsByMessageKey = [];
        $messages = $buffer->getMessagesForTopic($this->topic);
        foreach ($messages as $messageId => $message) {
            $messageData = $this->getMessageData($message);
            $messageKey = $this->getMessageKey($messageData);

            if (!isset($firstMessagesIds[$messageKey])) {
                $firstMessagesIds[$messageKey] = $messageId;
            } else {
                $buffer->removeMessage($messageId);
            }

            $entityIdsByMessageKey[$messageKey][] = (array) ($messageData['context']['entityIds'] ?? []);
        }

        if ($entityIdsByMessageKey) {
            foreach ($entityIdsByMessageKey as $messageKey => $entityIds) {
                if (count($entityIds) <= 1) {
                    continue;
                }

                $firstMessageId = $firstMessagesIds[$messageKey];

                /** @var array $firstMessage */
                $firstMessage = $buffer->getMessage($firstMessagesIds[$messageKey]);
                if ($firstMessage instanceof Message) {
                    $firstMessageBody = $firstMessage->getBody();
                    $firstMessageBody['context']['entityIds'] = $this->mergeEntityIds($entityIds);
                    $firstMessage->setBody($firstMessageBody);
                } else {
                    $firstMessage['context']['entityIds'] = $this->mergeEntityIds($entityIds);
                }

                $buffer->replaceMessage($firstMessageId, $firstMessage);
            }
        }
    }

    /**
     * @param array $entityIds
     *
     * @return array
     */
    private function mergeEntityIds(array $entityIds): array
    {
        return array_values(array_unique(array_merge(...$entityIds)));
    }

    /**
     * @param array $messageData
     *
     * @return string
     */
    private function getMessageKey(array $messageData): string
    {
        return sprintf(
            '%s|%s|%s',
            implode(',', (array) ($messageData['class'] ?? [])),
            implode(',', (array) ($messageData['context']['websiteIds'] ?? [])),
            $messageData['granulize'] ?? false
        );
    }
}

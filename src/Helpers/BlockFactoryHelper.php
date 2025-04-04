<?php

namespace OpenFunctions\Tools\Notion\Helpers;

use Notion\Blocks\BlockType;
use Notion\Blocks\BulletedListItem;
use Notion\Blocks\Code;
use Notion\Blocks\EquationBlock;
use Notion\Blocks\Heading1;
use Notion\Blocks\Heading2;
use Notion\Blocks\Heading3;
use Notion\Blocks\NumberedListItem;
use Notion\Blocks\Paragraph;
use Notion\Blocks\Quote;
use Notion\Blocks\ToDo;
use Notion\Blocks\Toggle;

class BlockFactoryHelper
{
    public static function fromString(string $blockType, string $content)
    {
        switch ($blockType) {
            case BlockType::BulletedListItem->value:
                return BulletedListItem::fromString($content);
            case BlockType::Code->value:
                return Code::fromString($content);
            case BlockType::Equation->value:
                return EquationBlock::fromString($content);
            case BlockType::Heading1->value:
                return Heading1::fromString($content);
            case BlockType::Heading2->value:
                return Heading2::fromString($content);
            case BlockType::Heading3->value:
                return Heading3::fromString($content);
            case BlockType::NumberedListItem->value:
                return NumberedListItem::fromString($content);
            case BlockType::Paragraph->value:
                return Paragraph::fromString($content);
            case BlockType::Quote->value:
                return Quote::fromString($content);
            case BlockType::ToDo->value:
                return ToDo::fromString($content);
            case BlockType::Toggle->value:
                return Toggle::fromString($content);
            default:
                throw new \Exception("Unsupported block type: $blockType");
        }
    }

    public static function getTypes(): array
    {
        return [
            BlockType::BulletedListItem->value,
            BlockType::Code->value,
            BlockType::Equation->value,
            BlockType::Heading1->value,
            BlockType::Heading2->value,
            BlockType::Heading3->value,
            BlockType::NumberedListItem->value,
            BlockType::Paragraph->value,
            BlockType::Quote->value,
            BlockType::ToDo->value,
            BlockType::Toggle->value,
        ];
    }
}
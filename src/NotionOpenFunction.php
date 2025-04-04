<?php

namespace OpenFunctions\Tools\Notion;

use OpenFunctions\Core\Contracts\AbstractOpenFunction;
use OpenFunctions\Core\Responses\Items\TextResponseItem;
use OpenFunctions\Core\Schemas\FunctionDefinition;
use OpenFunctions\Core\Schemas\Parameter;
use OpenFunctions\Tools\Notion\Helpers\BlockFactoryHelper;
use Notion\Blocks\BlockFactory;
use Notion\Notion;
use Notion\Pages\Page;
use Notion\Pages\PageParent;
use Notion\Search\Query;

class NotionOpenFunction extends AbstractOpenFunction
{
    private Notion $client;
    public function __construct($apiKey)
    {
        $this->client = Notion::create($apiKey);
    }

    /**
     * List all databases in the specified Notion workspace.
     *
     * @return TextResponseItem
     */
    public function listDatabases()
    {
        $query = Query::all()->filterByDatabases();
        $results = $this->client->search()->search($query)->results;

        return new TextResponseItem(json_encode($results));
    }

    /**
     * List all pages in the specified Notion workspace.
     *
     * @return TextResponseItem
     */
    public function listPages()
    {
        $query = Query::all()->filterByPages();
        $results = $this->client->search()->search($query)->results;

        return new TextResponseItem(json_encode($results));
    }

    /**
     * Retrieve details of the specified database in the Notion workspace.
     *
     * @param string $databaseId The ID of the database to retrieve.
     * @return TextResponseItem
     */
    public function getDatabase($databaseId)
    {
        $result = $this->client->databases()->find($databaseId)->toArray();

        return new TextResponseItem(json_encode($result));
    }

    /**
     * Create a new page in Notion.
     *
     * @param string $parentId The ID of the parent page or database where the new page will be created.
     * @param array $data Data for creating the page, including title and content blocks.
     * @return TextResponseItem
     */
    public function createPage($parentId, array $data)
    {
        $parent = PageParent::page($parentId);
        $page = Page::create($parent)
            ->changeTitle($data['title'] ?? 'Untitled');

        $blocks = [];
        $contentBlocks = $data['content'] ?? [];

        foreach ($contentBlocks as $blockContent) {
            $blocks[] = BlockFactoryHelper::fromString($blockContent['type'], $blockContent['text']);
        }

        $result = $this->client->pages()->create($page, $blocks)->toArray();

        return new TextResponseItem(json_encode($result));
    }

    /**
     * Update the title of an existing page in Notion.
     *
     * @param string $pageId The ID of the page to update.
     * @param string $newTitle The new title for the page.
     * @return TextResponseItem
     */
    public function updatePageTitle($pageId, $newTitle)
    {
        $page = $this->client->pages()->find($pageId);
        $page = $page->changeTitle($newTitle);
        $result = $this->client->pages()->update($page)->toArray();

        return new TextResponseItem(json_encode($result));
    }

    /**
     * Search pages in Notion with a query.
     *
     * @param string $query The search query.
     * @return TextResponseItem
     */
    public function searchPages(string $query)
    {
        $queryObj = Query::title($query);
        $results = $this->client->search()->search($queryObj)->results;

        return new TextResponseItem(json_encode($results));
    }

    /**
     * Retrieve the content of a block in Notion.
     *
     * @param string $blockId The ID of the block to retrieve.
     * @return TextResponseItem
     */
    public function retrieveBlockContent(string $blockId)
    {
        $children = $this->client->blocks()->findChildren($blockId);
        $blocksArray = [];
        foreach ($children as $child) {
            $blocksArray[] = $child->toArray();
        }

        return new TextResponseItem(json_encode($blocksArray));
    }

    /**
     * Update multiple block contents in Notion.
     *
     * @param array $blockUpdates An array of block updates. Each update should include block ID and new content.
     * @return TextResponseItem
     */
    public function updateBlockContent(array $blockUpdates)
    {
        foreach ($blockUpdates as $blockContent) {
            $block = BlockFactoryHelper::fromString($blockContent['type'], $blockContent['text']);
            $blockArray = $block->toArray();
            $blockArray["id"] = $blockContent["id"];
            $block = BlockFactory::fromArray($blockArray);
            $this->client->blocks()->update($block);
        }

        return new TextResponseItem(json_encode(true));
    }

    /**
     * Add new content blocks to a parent block or page in Notion.
     *
     * @param string $parentId The ID of the parent block or page to add content to.
     * @param array $blocks An array of new content blocks.
     * @return TextResponseItem
     */
    public function addBlockContent(string $parentId, array $blocks)
    {
        $blockObjects = [];
        foreach ($blocks as $blockData) {
            $block = BlockFactoryHelper::fromString($blockData['type'], $blockData['text']);
            $blockObjects[] = $block;
        }
        $this->client->blocks()->append($parentId, $blockObjects);

        return new TextResponseItem(json_encode(true));
    }

    /**
     * Delete a block in Notion by its ID.
     *
     * @param string $blockId The ID of the block to delete.
     * @return TextResponseItem
     */
    public function deleteBlockContent(string $blockId)
    {
        $this->client->blocks()->delete($blockId);

        return new TextResponseItem(json_encode(true));
    }

    /**
     * Generate function definitions for OpenAI function calling.
     *
     * @return array
     */
    public function generateFunctionDefinitions(): array
    {
        $result = [];

        // listDatabases
        $result[] = (new FunctionDefinition('listDatabases', 'List all databases in the specified Notion workspace.'))
            ->createFunctionDescription();

        // listPages
        $result[] = (new FunctionDefinition('listPages', 'List all pages in the specified Notion workspace.'))
            ->createFunctionDescription();

        // getDatabase
        $result[] = (new FunctionDefinition('getDatabase', 'Retrieve details of the specified database in the Notion workspace.'))
            ->addParameter(
                Parameter::string('databaseId')->description('The ID of the database to retrieve')->required()
            )
            ->createFunctionDescription();

        // createPage
        $createPageData = Parameter::object('data')->description('Data for creating a page.')->required();
        $createPageData
            ->addProperty(Parameter::string('title')->description('The title of the page')->required())
            ->addProperty(Parameter::array('content')->description('Blocks of content to include in the page.')
                ->required()
                ->setItems(
                    Parameter::object(null)->description('Attributes for each content block.')
                        ->addProperty(Parameter::string('type')->enum(BlockFactoryHelper::getTypes())->description('Type of the block')->required())
                        ->addProperty(Parameter::string('text')->description('Text content of the block')->required())
                )
            );

        $result[] = (new FunctionDefinition('createPage', 'Create a new page in Notion.'))
            ->addParameter(
                Parameter::string('parentId')->description('The ID of the parent page or database where the new page will be created')->required()
            )
            ->addParameter($createPageData)
            ->createFunctionDescription();

        // updatePageTitle
        $result[] = (new FunctionDefinition('updatePageTitle', 'Update the title of an existing page in Notion.'))
            ->addParameter(
                Parameter::string('pageId')->description('The ID of the page to update')->required()
            )
            ->addParameter(
                Parameter::string('newTitle')->description('The new title for the page')->required()
            )
            ->createFunctionDescription();

        // searchPages
        $result[] = (new FunctionDefinition('searchPages', 'Search pages in Notion with a query.'))
            ->addParameter(Parameter::string('query')->description('The search query')->required())
            ->createFunctionDescription();

        // retrieveBlockContent
        $result[] = (new FunctionDefinition('retrieveBlockContent', 'Retrieve the content of a block in Notion.'))
            ->addParameter(Parameter::string('blockId')->description('The ID of the block to retrieve')->required())
            ->createFunctionDescription();

        // updateBlockContent
        $blockUpdatesParameter = Parameter::array('blockUpdates')->description('An array of block updates. Each update includes block ID and new content.')->required()
            ->setItems(
                Parameter::object(null)->description('Attributes for each block update.')
                    ->addProperty(Parameter::string('id')->description('The ID of the block to update')->required())
                    ->addProperty(Parameter::string('type')->enum(BlockFactoryHelper::getTypes())->description('Type of the block')->required())
                    ->addProperty(Parameter::string('text')->description('New text content of the block')->required())
            );

        $result[] = (new FunctionDefinition('updateBlockContent', 'Update multiple block contents in Notion.'))
            ->addParameter($blockUpdatesParameter)
            ->createFunctionDescription();

        // addBlockContent
        $blocksParameter = Parameter::array('blocks')->description('An array of new content blocks to add. Each block can be a paragraph, heading, list, etc.')->required()
            ->setItems(
                Parameter::object(null)->description('Attributes for each content block.')
                    ->addProperty(Parameter::string('type')->enum(BlockFactoryHelper::getTypes())->description('Type of the block')->required())
                    ->addProperty(Parameter::string('text')->description('Text content of the block')->required())
            );

        $result[] = (new FunctionDefinition('addBlockContent', 'Add new content blocks to a parent block or page in Notion.'))
            ->addParameter(Parameter::string('parentId')->description('The ID of the parent block or page to add content to')->required())
            ->addParameter($blocksParameter)
            ->createFunctionDescription();

        // deleteBlockContent
        $result[] = (new FunctionDefinition('deleteBlockContent', 'Delete a block in Notion by its ID.'))
            ->addParameter(Parameter::string('blockId')->description('The ID of the block to delete')->required())
            ->createFunctionDescription();

        return $result;
    }
}
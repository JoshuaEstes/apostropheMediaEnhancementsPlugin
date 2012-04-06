<?php
/**
 * @package    apostrophePlugin
 * @subpackage    toolkit
 * @author     P'unk Avenue <apostrophe@punkave.com>
 */
class BaseaEnhancedMediaTools extends aMediaTools
{
    /**
     * Get an instance to work with.
     *
     * @return \aEnhancedMediaTools
     */
    static public function getInstance()
    {
        return new aEnhancedMediaTools();
    }

    /**
     * This function will handle moving files from the
     * apostrophe html5 uploader.  It must be able to
     * handle a POST with files or a straight XHR upload.
     * The post may include multiple files.
     *
     * Return an array of file paths that have been moved.
     *
     * @param sfWebRequest $request
     * @return array
     */
    public function handleHtml5Upload(sfWebRequest $request)
    {
        $result = array();

        if ($request->getGetParameter('aFile')) // XHR upload
        {
            $result[] = $this->handleXhrUpload($request);
        }
        else if ($request->getMethod() == 'POST') // POST upload
        {
            $files = $request->getFiles();

            if (!empty($files['aFile']))
            {
                if ($this->isSingleFile($files['aFile']))
                {
                    $result[] = $this->handlePostUpload($files['aFile']);
                }
                else
                {
                    foreach ($files['aFile'] as $file)
                    {
                        $result[] = $this->handlePostUpload($file);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Determines if the passed array is an array of uploaded
     * files or a single uploaded file.
     *
     * @param array $ar
     * @return boolean
     */
    public function isSingleFile($ar)
    {
        return (count($ar) == 5) && (!empty($ar['name']));
    }

    /**
     * Copies an uploaded file from standard input
     * to a tmp upload directory.
     *
     * @param sfWebRequest $request
     * @return boolean|string
     */
    protected function handleXhrUpload(sfWebRequest $request)
    {
        $uploadsDirectory = aFiles::getUploadFolder(array('batch_upload'));

        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != ((int)$_SERVER['CONTENT_LENGTH']))
        {
            return false;
        }

        $path = $uploadsDirectory . '/' . aTools::slugify($request->getGetParameter('aFile'));
        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return $path;
    }

    /**
     *
     * Copies a POSTed uploaded file to a temporary upload
     * directory.
     *
     * @param array $file
     * @return string|boolean
     */
    protected function handlePostUpload($file)
    {
        $uploadsDirectory = aFiles::getUploadFolder(array('batch_upload'));

        $path = $uploadsDirectory . '/' . aTools::slugify($file['name']);

        if (move_uploaded_file($file['tmp_name'], $path))
        {
            return $path;
        }
        
        return false;
    }

    /**
     * This takes an aMediaItem as input and returns an array suitable
     * for usage with our backbone model
     *
     *
     * @param MediaItem $item
     * @return array
     */
    public function toBackboneArray(aMediaItem $item, $ar = array())
    {
        $ar['id'] = $item->getId();
        $ar['viewUrl'] = url_for('a_media_image_show', array('slug' => $item->getSlug()));

        // this is a bad way to construct a URL. Update the routing to make this better.
        $ar['editUrl'] = url_for("aMedia/html5Edit?" . http_build_query(array("slug" => $item->getSlug())));
        $ar['deleteUrl'] = url_for("aMedia/delete?" . http_build_query(array("slug" => $item->getSlug())));
        $ar['tags'] = implode(',', $item->getTags());

        if ($item->type == 'image')
        {
            $ar['srcUrl'] = $item->getCropThumbnailUrl();
        } else {
            $ar['srcUrl'] = '';
        }

        return array_merge($ar, $item->toArray());
    }

    public function processNewCategories($newCategories)
    {
        if (!sfContext::getInstance()->getUser()->hasCredential(aMediaTools::getOption('admin_credential')))
        {
            return false;
        }

        $categories = array();
        if (!empty($newCategories))
        {
            foreach($newCategories as $cName)
            {
                $c = new aCategory();
                $c->name = $cName;
                $categories[] = $c;
            }
        }

        return $categories;
    }

    /**
     *
     * Implements edit and validation code for editing media items.
     *
     * Most of this code is taken from the original edit action.
     *
     * @param aMediaItem $item
     * @param array $params
     * @return aMediaItem $item
     */
    public function editItem(aMediaItem &$item, $params)
    {
        $form = new aMediaEditForm($item);
        unset($form['file'], $form['_csrf_token']);

        $newCategories = array();
        if (!empty($params['categories_add']))
        {
            $newCategories = $this->processNewCategories($params['categories_add']);
            if (!$newCategories)
            {
                return false;
            }
            unset($params['categories_add']);
        }

        $form->bind($params, array()); // Null files array
        if ($form->isValid())
        {
            // add categories and leave
            $object = $form->getObject();
            foreach($newCategories as $category)
            {
                $object->Categories[] = $category;
            }
            $object->save();

            return true;
        }

        return false;

//        $item->title = $params['title'];
//        $item->description = $params['description'];
//        $item->credit = $params['credit'];
//        $item->view_is_secure = ($params['is_secure'] == 1)? true : false;
//        $item->addTag($params['tags']);
//
//        if (!empty($params['categories']))
//        {
//            $categories = Doctrine::getTable('aCategory')->createQuery('c')
//                    ->andWhereIn('id', $params['categories'])
//                    ->execute();
//
//            foreach($categories as $c)
//            {
//                $item->Categories[] = $c;
//            }
//        }
    }
}

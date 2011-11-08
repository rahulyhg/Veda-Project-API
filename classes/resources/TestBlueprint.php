<?php

require_once("classes/resources/Material.php");

class TestBlueprint {

    protected $childData = null; //holds questionBlueprints, is an array
    protected $content_type = null; //for lesson, course, or sectuo
    protected $content_id = null; //other part of unique id. [lesson][12] is the test for lesson 12, [course][12] is for course 12
    protected $number_of_questions = null; //pretty obvious
    protected $id = null; //id for this blueprint
    protected $JSONData = null;


    ########################################################
    #### Helper functions for loading object ###############
    ########################################################

    public function loadFromUri($uri) { //grab blueprint info from db
        //!!!!!!Make this not die on fail!!!!!!
        //get BP by its ID
        $uriArr = explode("/", trim($uri, "/")); //puts all the stuff like etc/foo/blah into array with {etc, foo, blah}
        $this->id = $uriArr[4]; //index in URI for BPs
        $this->loadFromID();
        return true;
    }

    public function loadFromId() {
        $query = sprintf("SELECT * FROM test_blueprint WHERE id = %s", pg_escape_string($this->id)); //get all the stuff from the row where id=the id of the test we want
        $result = $GLOBALS["transaction"]->query($query); //store all the stuff from the row into an array called $results

        $this->content_type = $result["content_type"];
        $this->content_id = $reult["content_id"];
        $this->number_of_questions = $result["number_of_questions"];
        $this->loadChildData();
    }

    public function loadChildData() {//stores question blueprint data into $childData
        $query = sprintf("SELECT * FROM question_attachment WHERE test_id = %s", pg_escape_string($this->id)); //gets IDs and revision_ids related to this test
        $questionAttachData = $GLOBALS["transaction"]->query($query); //result should contain a bunch of question attachment data
        for ($i = 0; $i < sizeOf($questionAttachData); $i++) {
            //the following should load each question blueprint by ID and revision ID
            $query = sprintf("SELECT * FROM question_blueprint WHERE id = %s AND revision_id = %s", pg_escape_string($questionAttachData[i]["question_id"]), pg_escape_string($questionAttachData[i]["question_revision_id"]));
            $questionData = $GLOBALS["transaction"]->query($query);
            $this->childData[i] = $questionData; //now, each element of childData should hold all the data from a question blueprint
        }
        if (sizeOf($this->childData) > sizeOf($questionAttachData)) {//if there are extra elements in childData that no longer exist in the DB
            for ($i = sizeOf($questionAttachData); $i < sizeOf($this->childData); $i++) {
                unset($this->childData[i]); //clears old childData
            }
        }
    }

    public function buildJSON() {//gets data, stores it as JSON
        $this->JSONData = json_encode(array("content_type" => $this->content_type, "content_id" => $this->content_id, "number_of_questions" => $this->number_of_questions, "child_data" => $this->childData));
    }

    public function getJSON() {//returns stored data
        return $this->JSONData;
    }

    ############################################
    ######setters/getters#####################
    #######################################

    public function setNumberOfQuestions($number) { //sets number of questions in test
        $this->number_of_questions = $number;
    }

    public function getNumberOfQuestions() {
        return $this->number_of_questions;
    }

    public function setContentType($type) { //sets number of questions in test
        $this->content_type = $type;
    }

    public function getContentType() {
        return $this->content_type;
    }

    public function setContentId($id) { //sets number of questions in test
        $this->content_id = $id;
    }

    public function getContentId() {
        return $this->content_id;
    }

    ########################################################
    #### Database interface functions ###############
    ########################################################

    public function AttachQuestionBlueprints($question_id_array) {//modifies question attachment table. $question_id_array should have both id and weight for each question
        for ($i = 0; $i < sizeOf($question_id_array); $i++) {
            $query = sprintf("SELECT revision_id FROM question_blueprints WHERE question_id=%s ORDER BY revision_date DESC LIMIT 1", pg_escape_string($question_id_array[i]["id"])); //get the latest revision ID
            $latest_revision_id = $GLOBALS['transaction']->query($query);
            $query = sprintf("INSERT INTO question_attachment (question_id, test_id, weight, question_revision_id) VALUES (%s, %s, %s, %s)", pg_escape_string($question[i]["id"]), pg_escape_string($this->id), pg_escape_string($question[i]["weight"]), pg_escape_string($latest_revision_id)); //attaches questions to test blueprint in DB with specified ID and latest revision ID
            $GLOBALS['transaction']->query($query);
        }
        $this->loadChildData(); //reloads child data with new questions included
    }

    public function save() {
        $query = sprintf("SELECT revision_id FROM test_blueprint WHERE id=%s ORDER BY revision_date DESC LIMIT 1", pg_escape_string($this->id)); //get the previous revision ID for this test
        $latest_revision_id = $GLOBALS['transaction']->query($query); //this is a STRING
        $latest_revision_id = intval($latest_revision_id) + 1; //latest_revision_id is now INCREMENTED
        $query = sprintf("INSERT INTO test_blueprint (id, content_type, content_id, number_of_questions, revision_id) VALUES (%s, %s, %s, %s, %s)", pg_escape_string($this->id), pg_escape_string($this->content_type), pg_escape_string($this->content_id), pg_escape_string($this->number_of_questions), pg_escape_string($latest_revision_id));
    }

}
?> 

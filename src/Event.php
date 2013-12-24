<?php
// src/Event.php
/**
 * @Entity @Table(name="Event")
  **/
class Event {
    /**
     * @var int
     */
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
     * @var int
     */
    /** @Column(type="date") **/
    protected $date;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=true) **/
    protected $description;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=true) **/
    protected $type;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=true) **/
    protected $source;

    /** @Column(type="boolean") **/
    protected $is_published = 0;

    public function getId() {
        return $this->id;
    }

    public function getDate() {
        return $this->date;
    }

    public function setDate(DateTime $date) {
        $this->date = $date;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getSource() {
        return $this->source;
    }

    public function setSource($source) {
        $this->source = $source;
    }

    public function getIsPublished() {
        return $this->is_published;
    }

    public function setIsPublished($is_published) {
        $this->is_published = $is_published;
    }
}

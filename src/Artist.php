<?php
// src/Artist.php
/**
 * @Entity @Table(name="Artist")
 */
class Artist {

    /**
     * @var int
     */
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=true) **/
    protected $name;

    /**
     * @OneToMany(targetEntity="Event", mappedBy="artist")
     * @var Event[]
     **/
    protected $events;

    public function __construct() {
        $this->events = new ArrayCollection();
    }

    public function assignToEvent( Event $event) {
        $this->events[] = $event;
    }

    public function getProducts()
    {
        return $this->products;
    }

    /*Getters and setters*/
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }
}

?>

<?php
use Doctrine\Common\Collections\ArrayCollection;

// src/Artist.php
/**
 * @Entity @Table(name="Artist",indexes={@index(name="name_idx", columns={"name"})})
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
    /** @Column(type="string", unique=true, nullable=false) **/
    protected $name;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=true) **/
    protected $spotifyId;

    /**
     * @var boolean 
     */
    /** @Column(type="boolean", nullable=true) **/
    protected $hasTracks;

    /**
     * @OneToMany(targetEntity="Event", mappedBy="artist")
     * @var Event[]
     **/
    protected $events;

    /**
     * @OneToMany(targetEntity="Track", mappedBy="artist")
     * @var Track[]
     **/
    protected $tracks;

    public function __construct() {
        $this->events = new ArrayCollection();
        $this->tracks = new ArrayCollection();
    }

    public function assignToEvent( Event $event) {
        $this->events[] = $event;
    }

    public function addTrack( Track $track ) {
        $this->tracks[] = $track;
    }

    public function getEvents() {
        return $this->events;
    }

    public function getTracks() {
        return $this->tracks;
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

    public function getSpotifyId() {
        return $this->spotifyId;
    }

    public function setSpotifyId($spotifyId) {
        $this->spotifyId = $spotifyId;
    }

    public function getHasTracks() {
        return $this->hasTracks;
    }

    public function setHasTracks($hasTracks) {
        $this->hasTracks = $hasTracks;
    }
}

?>

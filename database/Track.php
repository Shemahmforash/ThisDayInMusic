<?php
// src/Track.php

/**
 * @Entity @Table(name="Track")
**/
class Track {
    /**
     * @var int
     */
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=false) **/
    protected $name;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=true) **/
    protected $spotifyId;

    /**
     * @ManyToOne(targetEntity="Artist", inversedBy="tracks")
     **/ 
    protected $artist;

    /**
     * @OneToOne(targetEntity="Event", inversedBy="track")
     **/ 
    protected $event;

    public function assignToArtist( Artist $artist ) {
        $this->artist = $artist;
    }

    public function getArtist() {
        return $this->artist;
    }

    public function assignToEvent( Event $event) {
        $this->events = $event;
    }

    public function getEvent() {
        return $this->event;
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
}
?>

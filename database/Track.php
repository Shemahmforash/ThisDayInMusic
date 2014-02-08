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

    public function assignToArtist( Artist $artist ) {
        $this->artist = $artist;
    }

    public function getArtist() {
        return $this->artist;
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

<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output method="html"
                encoding="UTF-8"
                omit-xml-declaration="yes"/>

    <!-- Zoekt uitbreidingen op hoofdspel-ID en hoofdspellen op hun eigen ID -->
    <xsl:key name="extensions" match="extension" use="base_game"/>
    <xsl:key name="base-games" match="game" use="id"/>

    <xsl:template match="/">

        <!-- Complete groepen eerst, gesorteerd van groot naar klein -->
        <div class="evening-grid">
            <xsl:for-each select="games/game[key('extensions', id)]">

                <xsl:sort select="count(key('extensions', id))"
                          data-type="number"
                          order="descending"/>

                <xsl:variable name="size"
                    select="1 + count(key('extensions', id))"/>

                <!-- Binnen een groep staan maximaal vier dozen naast elkaar -->
                <xsl:variable name="columns">
                    <xsl:choose>
                        <xsl:when test="$size &gt; 4">4</xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="$size"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>

                <div>
                    <xsl:attribute name="class">
                        <xsl:choose>
                            <xsl:when test="$size &gt; 2">
                                <xsl:text>evening-wide</xsl:text>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:text>evening-pair</xsl:text>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:attribute>

                    <xsl:call-template name="group">
                        <xsl:with-param name="columns" select="$columns"/>
                    </xsl:call-template>
                </div>
            </xsl:for-each>
        </div>

        <!-- Losse hoofdspellen beginnen op een nieuwe rij -->
        <div class="evening-grid">
            <xsl:for-each select="games/game[not(key('extensions', id))]">
                <xsl:call-template name="group"/>
            </xsl:for-each>
        </div>

        <!-- Losse uitbreidingen worden eveneens weergegeven -->
        <div class="evening-grid">
            <xsl:for-each select="games/extension[not(key('base-games', base_game))]">
                <xsl:call-template name="group"/>
            </xsl:for-each>
        </div>

        <xsl:if test="not(games/*)">
            <p>De XML bevat geen spellen.</p>
        </xsl:if>
    </xsl:template>

    <!-- Eén kader bevat een hoofdspel met zijn uitbreidingen, of een los item -->
    <xsl:template name="group">
        <xsl:param name="columns" select="1"/>

        <section class="evening-group"
                 style="grid-column: span {$columns};">

            <xsl:if test="self::extension">
                <p>
                    Hoofdspel van deze uitbreiding ontbreekt in deze XML.
                </p>
            </xsl:if>

            <div class="evening-cards"
                 style="grid-template-columns: repeat({$columns}, minmax(0, 1fr));">

                <!-- Het hoofdspel wordt altijd voor zijn uitbreidingen geplaatst -->
                <xsl:apply-templates select="." mode="card"/>

                <xsl:if test="self::game">
                    <xsl:apply-templates
                        select="key('extensions', id)"
                        mode="card"/>
                </xsl:if>
            </div>
        </section>
    </xsl:template>

    <!-- De gegevens van ieder spel staan verticaal onder zijn doosfoto -->
    <xsl:template match="game | extension" mode="card">
        <div class="evening-card">
            <div class="evening-photo">
                <xsl:choose>
                    <xsl:when test="@photo_src != ''">
                        <img src="{@photo_src}"
                             alt="{title | extension_name}"/>
                    </xsl:when>
                    <xsl:otherwise>
                        Geen foto beschikbaar
                    </xsl:otherwise>
                </xsl:choose>
            </div>

            <h3>
                <xsl:value-of select="title | extension_name"/>
            </h3>
            
            <p>
                Eigenaar:
                <xsl:choose>
                    <xsl:when test="normalize-space(owner_main | owner_extension) != ''">
                        <xsl:value-of select="owner_main | owner_extension"/>
                    </xsl:when>
                    <xsl:otherwise>Onbekend</xsl:otherwise>
                </xsl:choose>
            </p>

            <p>
                Spelers: <xsl:value-of select="@players_text"/>
            </p>

            <xsl:choose>
                <xsl:when test="self::game">
                    <p>
                        Categorie: <xsl:value-of select="category"/>
                    </p>
                </xsl:when>
                <xsl:otherwise>
                    <p>Dit is een uitbreidingsset</p>
                </xsl:otherwise>
            </xsl:choose>

            <p>
                Bordspel: <xsl:value-of select="@board_game"/>
            </p>

            <xsl:if test="@board_game = 'Ja'">
                <p>
                    Bordformaat: <xsl:value-of select="@board_text"/>
                </p>
            </xsl:if>
        </div>
    </xsl:template>

</xsl:stylesheet>
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
    <!ENTITY euro  "&#8364;">
    <!ENTITY ndash "&#8211;">
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output
        method="xml"
        indent="no"
        encoding="utf-8"
        doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
        doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
        omit-xml-declaration="no"
    />
    
    <xsl:template match="/">
        <html>
            <head>
                <style>@import 'style/table.css';</style>
            </head>
            <body>
                <xsl:apply-templates/>
            </body>
        </html>
    </xsl:template>
    
    <xsl:template match="/Root">
        <xsl:for-each select="HotelDescription[@Language='ru']">
            <xsl:variable name="en" select="../HotelDescription[@Language='en']"/>
            <table>
                <tr>
                    <td colspan="2">
                        <span class="title_address">
                            <xsl:value-of select="$en/Country/@Name"/>
                            -
                            <xsl:value-of select="$en/City/@Name"/>
                            -
                        </span>
                        <span class="title_rating">
                            <xsl:value-of select="$en/Rating/@Name"/>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="title_en_name">
                            <xsl:value-of select="$en/@Name"/>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <xsl:value-of select="@Name"/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <xsl:for-each select="Amenities/Amenity">
                            <img src="{@Url}" title="{@Name}"/>
                            <xsl:if test="@Code = 9">
                                <xsl:if test="$en/@NumberOfRestaurants &gt; 1">
                                    <xsl:value-of select="$en/@NumberOfRestaurants"/>
                                </xsl:if>
                            </xsl:if>
                            <xsl:if test="@Code = 10">
                                <xsl:if test="$en/@NumberOfBars &gt; 1">
                                    <xsl:value-of select="$en/@NumberOfBars"/>
                                </xsl:if>
                            </xsl:if>
                        </xsl:for-each>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            Built in
                            <xsl:value-of select="@Built"/>,
                            reconstructed in
                            <xsl:value-of select="@Reconstructed"/>,
                            floors - 
                            <xsl:value-of select="@NumberOfFloors"/>,
                            rooms - 
                            <xsl:value-of select="@NumberOfRooms"/>
                        </p>
                        <p>
                            Построен в
                            <xsl:value-of select="@Built"/> г.,
                            реконструирован в
                            <xsl:value-of select="@Reconstructed"/> г.,
                            этажей - 
                            <xsl:value-of select="@NumberOfFloors"/>,
                            номеров - 
                            <xsl:value-of select="@NumberOfRooms"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:value-of select="$en/@Address"/>,
                            <xsl:value-of select="$en/City/@Name"/>,
                            <xsl:value-of select="@Zip"/>,
                            <xsl:value-of select="$en/Country/@Name"/>
                        </p>
                        <p>
                            <xsl:value-of select="Country/@Name"/>,
                            <xsl:value-of select="@Zip"/>,
                            <xsl:value-of select="City/@Name"/>,
                            <xsl:value-of select="@Address"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:value-of select="$en/@Description"/>
                        </p>
                        <p>
                            <xsl:value-of select="@Description"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:value-of select="@WebAddress"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:for-each select="GDSystems/GDS">
                                <xsl:if test="position() &gt; 1">
                                    <xsl:text>, </xsl:text>
                                </xsl:if>
                                <xsl:value-of select="@Name"/>
                                <xsl:text> </xsl:text>
                                <xsl:value-of select="@Code"/>
                            </xsl:for-each>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            Rack rates/Открытые цены:
                            &euro; <xsl:value-of select="@RackRateMin"/> &ndash;
                            &euro; <xsl:value-of select="@RackRateMax"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:value-of select="@Phone"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            GMT:
                            <xsl:choose>
                                <xsl:when test="@Gmt &gt; 0">+</xsl:when>
                                <xsl:when test="@Gmt &lt; 0">&ndash;</xsl:when>
                            </xsl:choose>
                            <xsl:value-of select="@Gmt"/>
                            <xsl:if test="@GmtW and @GmtW != @Gmt">
                                /
                                <xsl:choose>
                                    <xsl:when test="@GmtW &gt; 0">+</xsl:when>
                                    <xsl:when test="@GmtW &lt; 0">&ndash;</xsl:when>
                                </xsl:choose>
                                <xsl:value-of select="@GmtW"/>
                            </xsl:if>
                            (win./sum., летн./зимн.)
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            in <xsl:value-of select="@CheckIn"/> /
                            out <xsl:value-of select="@CheckOut"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:for-each select="PaySystems/PaySystem">
                                <xsl:if test="position() &gt; 1">
                                    <xsl:text>, </xsl:text>
                                </xsl:if>
                                <xsl:value-of select="@Name"/>
                            </xsl:for-each>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            max <xsl:value-of select="@ConferenceHallMax"/> pax/conference hall
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:value-of select="$en/@CityCentre"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:choose>
                                <xsl:when test="$en/@Underground != ''">
                                    <xsl:value-of select="$en/@Underground"/>
                                </xsl:when>
                                <xsl:otherwise>-</xsl:otherwise>
                            </xsl:choose>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:choose>
                                <xsl:when test="$en/@RailwayStation != ''">
                                    <xsl:value-of select="$en/@RailwayStation"/>
                                </xsl:when>
                                <xsl:otherwise>-</xsl:otherwise>
                            </xsl:choose>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:choose>
                                <xsl:when test="$en/@Airport != ''">
                                    <xsl:value-of select="$en/@Airport"/>
                                </xsl:when>
                                <xsl:otherwise>-</xsl:otherwise>
                            </xsl:choose>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        [иконка]
                    </td>
                    <td>
                        <p>
                            <xsl:choose>
                                <xsl:when test="$en/@RiverPort != ''">
                                    <xsl:value-of select="$en/@RiverPort"/>
                                </xsl:when>
                                <xsl:otherwise>-</xsl:otherwise>
                            </xsl:choose>
                        </p>
                    </td>
                </tr>
            </table>
        </xsl:for-each>
    </xsl:template>
    
    <!-- сборка мусора -->
    <xsl:template match="*">0</xsl:template>
</xsl:stylesheet>

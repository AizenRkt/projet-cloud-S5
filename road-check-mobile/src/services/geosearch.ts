export interface GeoSearchResult {
  place_id: string;
  licence: string;
  osm_type: string;
  osm_id: string;
  boundingbox: [string, string, string, string];
  lat: string;
  lon: string;
  display_name: string;
  class: string;
  type: string;
  importance: number;
  icon?: string;
}

export interface SearchLocation {
  id: string;
  name: string;
  displayName: string;
  latitude: number;
  longitude: number;
  type: string;
  importance: number;
  boundingBox: {
    minLat: number;
    maxLat: number;
    minLon: number;
    maxLon: number;
  };
}

class GeoSearchService {
  private readonly baseUrl = 'https://nominatim.openstreetmap.org/search';
  private readonly defaultParams = {
    format: 'json',
    addressdetails: '1',
    limit: '10',
    countrycodes: 'mg', // Madagascar
    'accept-language': 'fr,en'
  };

  /**
   * Rechercher des endroits par nom/adresse
   */
  async searchPlaces(query: string): Promise<SearchLocation[]> {
    if (!query || query.trim().length < 2) {
      return [];
    }

    try {
      const params = new URLSearchParams({
        ...this.defaultParams,
        q: query.trim()
      });

      const response = await fetch(`${this.baseUrl}?${params}`, {
        headers: {
          'User-Agent': 'RoadCheckMobile/1.0 (Contact: admin@roadcheck.mg)'
        }
      });

      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      const results: GeoSearchResult[] = await response.json();
      return this.transformResults(results);
    } catch (error) {
      console.error('Erreur lors de la recherche géographique:', error);
      throw new Error('Impossible de rechercher cet endroit');
    }
  }

  /**
   * Rechercher spécifiquement dans Madagascar
   */
  async searchInMadagascar(query: string): Promise<SearchLocation[]> {
    const madagascarQuery = `${query}, Madagascar`;
    return this.searchPlaces(madagascarQuery);
  }

  /**
   * Rechercher par catégorie (hôpital, école, etc.)
   */
  async searchByCategory(category: string, near?: { lat: number; lon: number }): Promise<SearchLocation[]> {
    let query = category;
    if (near) {
      query += ` near ${near.lat},${near.lon}`;
    }
    query += ', Madagascar';
    
    return this.searchPlaces(query);
  }

  /**
   * Géocodage inverse: obtenir l'adresse à partir des coordonnées
   */
  async reverseGeocode(lat: number, lon: number): Promise<string | null> {
    try {
      const params = new URLSearchParams({
        format: 'json',
        lat: lat.toString(),
        lon: lon.toString(),
        zoom: '18',
        addressdetails: '1',
        'accept-language': 'fr,en'
      });

      const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${params}`, {
        headers: {
          'User-Agent': 'RoadCheckMobile/1.0 (Contact: admin@roadcheck.mg)'
        }
      });

      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      const result = await response.json();
      return result.display_name || null;
    } catch (error) {
      console.error('Erreur lors du géocodage inverse:', error);
      return null;
    }
  }

  /**
   * Transformer les résultats de l'API en format interne
   */
  private transformResults(results: GeoSearchResult[]): SearchLocation[] {
    return results.map(result => ({
      id: result.place_id,
      name: this.extractPlaceName(result.display_name),
      displayName: result.display_name,
      latitude: parseFloat(result.lat),
      longitude: parseFloat(result.lon),
      type: result.type,
      importance: result.importance,
      boundingBox: {
        minLat: parseFloat(result.boundingbox[0]),
        maxLat: parseFloat(result.boundingbox[1]),
        minLon: parseFloat(result.boundingbox[2]),
        maxLon: parseFloat(result.boundingbox[3])
      }
    }));
  }

  /**
   * Extraire le nom principal d'un lieu depuis display_name
   */
  private extractPlaceName(displayName: string): string {
    const parts = displayName.split(',');
    return parts[0].trim();
  }

  /**
   * Obtenir des suggestions de recherche populaires pour Madagascar
   */
  getPopularSuggestions(): string[] {
    return [
      'Antananarivo',
      'Toamasina',
      'Antsirabe',
      'Fianarantsoa',
      'Mahajanga',
      'Toliara',
      'Antsiranana',
      'Avenue de l\'Indépendance',
      'Analakely',
      'Behoririka',
      'Hôpital',
      'École',
      'Université',
      'Marché',
      'Gare routière'
    ];
  }
}

// Instance singleton
export default new GeoSearchService();
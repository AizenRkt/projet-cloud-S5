import { Geolocation, PositionOptions, Position } from "@capacitor/geolocation";

export default class GeolocalisationService {

  static async getCurrentPosition(): Promise<Position | null> {
    try {
      const permission = await Geolocation.requestPermissions();
      if (permission.location !== 'granted') return null;

      const position = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true
      } as PositionOptions);
      return position;
    } catch (err) {
      console.error("Impossible d'obtenir la position :", err);
      return null;
    }
  }

  static async watchPosition(
    callback: (position: Position | null, err?: any) => void
  ): Promise<string> {   // <-- string, pas number
    const permission = await Geolocation.requestPermissions();
    if (permission.location !== 'granted') return "-1";

    const watchId = await Geolocation.watchPosition(
      { enableHighAccuracy: true },
      (position, err) => callback(position, err)
    );
    return watchId;  
  }

  static async clearWatch(watchId: string) {
    await Geolocation.clearWatch({ id: watchId });
  }
}

import Foundation

struct Category: Identifiable, Hashable {
    let id = UUID()
    let name: String
    let symbol: String
}

struct Listing: Identifiable, Hashable {
    let id: UUID
    var title: String
    var category: String
    var price: Int
    var location: String
    var details: String
    var seller: String
    var symbol: String
    var isFavourite: Bool = false
}

extension Listing {
    static let samples: [Listing] = [
        .init(id: UUID(), title: "Skyranger Swift", category: "Aircraft", price: 28500, location: "Yorkshire", details: "Well maintained microlight. Prototype advert showing the future SkyMart aircraft listing experience.", seller: "Richard", symbol: "airplane"),
        .init(id: UUID(), title: "Trig TT21 Mode S Transponder", category: "Avionics", price: 975, location: "Lancashire", details: "Compact Mode S transponder in good condition. Includes tray and connector.", seller: "Aero Seller", symbol: "antenna.radiowaves.left.and.right"),
        .init(id: UUID(), title: "3 1/8 inch Altimeter", category: "Instruments", price: 325, location: "Cumbria", details: "Serviceable panel altimeter removed during avionics upgrade.", seller: "North Air", symbol: "gauge.with.dots.needle.67percent"),
        .init(id: UUID(), title: "ANR Aviation Headset", category: "Pilot Equipment", price: 420, location: "Leeds", details: "Active noise reduction headset with carry case.", seller: "Pilot Shop", symbol: "headphones")
    ]
}

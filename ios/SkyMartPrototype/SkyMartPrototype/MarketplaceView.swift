import SwiftUI

struct MarketplaceView: View {
 @EnvironmentObject var store: MarketplaceStore
 private let columns=[GridItem(.adaptive(minimum:160),spacing:14)]
 var body: some View {
  ScrollView {
   VStack(alignment:.leading,spacing:20) {
    ZStack(alignment:.bottomLeading) {
     LinearGradient(colors:[.blue.opacity(.75),.indigo.opacity(.85)],startPoint:.topLeading,endPoint:.bottomTrailing)
     Image(systemName:"airplane").font(.system(size:110,weight:.thin)).foregroundStyle(.white.opacity(.18)).offset(x:220,y:-18)
     VStack(alignment:.leading,spacing:7) {
      Text("THE AVIATION MARKETPLACE").font(.caption.bold()).tracking(1.5).foregroundStyle(.white.opacity(.8))
      Text("Find your next\naviation adventure").font(.largeTitle.bold()).foregroundStyle(.white)
      Text("Aircraft • avionics • parts • pilot equipment").font(.subheadline).foregroundStyle(.white.opacity(.85))
     }.padding(22)
    }.frame(height:220).clipShape(RoundedRectangle(cornerRadius:24))
    VStack(alignment:.leading,spacing:10) {
     Text("Browse categories").font(.title2.bold())
     ScrollView(.horizontal,showsIndicators:false) {
      HStack(spacing:10) {
       Button {store.selectedCategory=nil} label:{CategoryChip(name:"All",symbol:"square.grid.2x2",selected:store.selectedCategory==nil)}
       ForEach(store.categories){c in Button {store.selectedCategory=c.name} label:{CategoryChip(name:c.name,symbol:c.symbol,selected:store.selectedCategory==c.name)}}
      }
     }
    }
    HStack { Text(store.selectedCategory ?? "Latest adverts").font(.title2.bold()); Spacer(); Text("\(store.filtered.count) adverts").font(.caption).foregroundStyle(.secondary) }
    LazyVGrid(columns:columns,spacing:14) {
     ForEach(store.filtered){listing in NavigationLink(value:listing){ListingCard(listing:listing)}.buttonStyle(.plain)}
    }
   }.padding()
  }
  .background(Color(.systemGroupedBackground))
  .navigationTitle("SkyMart").navigationBarTitleDisplayMode(.inline)
  .searchable(text:$store.query,prompt:"Aircraft, avionics, parts…")
  .navigationDestination(for:Listing.self){ListingDetailView(listingID:$0.id)}
 }
}
struct CategoryChip: View {
 let name:String;let symbol:String;let selected:Bool
 var body:some View { VStack(spacing:7){Image(systemName:symbol).font(.title2);Text(name).font(.caption.bold()).lineLimit(1)}.frame(width:92,height:76).background(selected ? Color.accentColor.opacity(.18):Color(.secondarySystemGroupedBackground)).clipShape(RoundedRectangle(cornerRadius:16)).overlay(RoundedRectangle(cornerRadius:16).stroke(selected ? Color.accentColor:.clear,lineWidth:1.5)) }
}
struct ListingCard:View {
 let listing:Listing
 var body:some View { VStack(alignment:.leading,spacing:9) {
  ZStack(alignment:.topTrailing){RoundedRectangle(cornerRadius:14).fill(LinearGradient(colors:[.gray.opacity(.13),.gray.opacity(.28)],startPoint:.top,endPoint:.bottom)).frame(height:125);Image(systemName:listing.symbol).font(.system(size:50,weight:.light)).foregroundStyle(.secondary);if listing.isFavourite{Image(systemName:"heart.fill").foregroundStyle(.red).padding(10)}}
  Text(listing.title).font(.headline).lineLimit(2).frame(maxWidth:.infinity,alignment:.leading)
  Text(listing.price,format:.currency(code:"GBP").precision(.fractionLength(0))).font(.title3.bold())
  Label(listing.location,systemImage:"mappin").font(.caption).foregroundStyle(.secondary)
 }.padding(10).background(Color(.secondarySystemGroupedBackground)).clipShape(RoundedRectangle(cornerRadius:18)) }
}
struct ListingRow:View {let listing:Listing;var body:some View{HStack{Image(systemName:listing.symbol).frame(width:44,height:44).background(.thinMaterial).clipShape(RoundedRectangle(cornerRadius:9));VStack(alignment:.leading){Text(listing.title).font(.headline);Text(listing.price,format:.currency(code:"GBP").precision(.fractionLength(0))).fontWeight(.semibold);Text(listing.location).font(.caption).foregroundStyle(.secondary)};Spacer()}}}

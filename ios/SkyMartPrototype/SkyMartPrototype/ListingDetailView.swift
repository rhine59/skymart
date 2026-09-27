import SwiftUI
struct ListingDetailView:View {
 @EnvironmentObject var store:MarketplaceStore;let listingID:UUID
 var listing:Listing?{store.listings.first{$0.id==listingID}}
 var body:some View {if let l=listing{ScrollView{VStack(alignment:.leading,spacing:18){
  Group{if l.images.isEmpty{ZStack{LinearGradient(colors:[.gray.opacity(.18),.gray.opacity(.38)],startPoint:.top,endPoint:.bottom);Image(systemName:l.symbol).font(.system(size:100,weight:.ultraLight)).foregroundStyle(.secondary)}}else{TabView{ForEach(Array(l.images.enumerated()),id:\.offset){_,image in AsyncImage(url:image.url){phase in switch phase{case .success(let img):img.resizable().scaledToFill();case .failure:ContentUnavailableView("Image unavailable",systemImage:"photo");default:ProgressView()}}}}.tabViewStyle(.page(indexDisplayMode:.automatic))}}.frame(height:300).clipShape(RoundedRectangle(cornerRadius:22))
  HStack(alignment:.top){VStack(alignment:.leading,spacing:5){Text(l.title).font(.largeTitle.bold());Label(l.location,systemImage:"mappin.and.ellipse").foregroundStyle(.secondary)};Spacer();Button{store.toggleFavourite(l)}label:{Image(systemName:l.isFavourite ? "heart.fill":"heart").font(.title2).foregroundStyle(l.isFavourite ? .red:.primary)}}
  Text(l.price,format:.currency(code:"GBP").precision(.fractionLength(0))).font(.title.bold());Divider();Text("About this advert").font(.title3.bold());Text(l.details);Divider();HStack{Image(systemName:"person.crop.circle.fill").font(.largeTitle);VStack(alignment:.leading){Text("Seller").font(.caption).foregroundStyle(.secondary);Text(l.seller).font(.headline)}}
  Button{}label:{Label("Contact seller",systemImage:"message.fill").frame(maxWidth:.infinity)}.buttonStyle(.borderedProminent).controlSize(.large).disabled(true)
 }.padding()}}.navigationTitle("Advert").navigationBarTitleDisplayMode(.inline)}else{ContentUnavailableView("Advert unavailable",systemImage:"exclamationmark.triangle")}}
}

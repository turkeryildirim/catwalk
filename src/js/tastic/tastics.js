import AccountLogin from './account/login/tastic.jsx'
import Cart from './cart/tastic.jsx'
import Checkout from './checkout/tastic.jsx'
import Content from './content/tastic.jsx'
import ContentList from './contentList/tastic.jsx'
import CategoryDescription from './categoryDescription/tastic.jsx'
import CategoryImage from './categoryImage/tastic.jsx'
import ImageBanner from './imageBanner/tastic.jsx'
import Markdown from './markdown/tastic.jsx'
import Navigation from './navigation/tastic.jsx'
import Ordered from './ordered/tastic.jsx'
import Product from './product/tastic.jsx'
import ProductTitle from './product/title/tastic.jsx'
import ProductImage from './product/image/tastic.jsx'
import ProductBrand from './product/brand/tastic.jsx'
import ProductPrice from './product/price/tastic.jsx'
import ProductAddToCart from './product/addToCart/tastic.jsx'
import ProductDescription from './product/description/tastic.jsx'
import ProductStock from './product/stock/tastic.jsx'
import ProductList from './productList/tastic.jsx'
import ProductListFilter from './productListFilter/tastic.jsx'
import ProductSlider from './productSlider/tastic.jsx'
import Teaser from './teaser/tastic.jsx'
import Text from './text/tastic.jsx'

export default (() => {
    return {
        'account-login': AccountLogin,
        'cart': Cart,
        'checkout': Checkout,
        'content': Content,
        'contentList': ContentList,
        'categoryDescription': CategoryDescription,
        'categoryImage': CategoryImage,
        'imageBanner': ImageBanner,
        'markdown': Markdown,
        'navigation': Navigation,
        'ordered': Ordered,
        'productList': ProductList,
        'productListFilter': ProductListFilter,
        'product': Product,
        'product-title': ProductTitle,
        'product-image': ProductImage,
        'product-brand': ProductBrand,
        'product-addToCart': ProductAddToCart,
        'product-description': ProductDescription,
        'product-stock': ProductStock,
        'product-price': ProductPrice,
        'productSlider': ProductSlider,
        'teaser': Teaser,
        'text': Text,
    }
})()
